/**
 * Provides language checking in the Summernote editor.
 * Uses LanguageTool to detect grammar and spelling issues and display them inline.
 */

class LanguageChecker {
    constructor(editorJq, options = {}) {
        this.$editor = editorJq; // jQuery object bound to textarea
        this.options = {
            debounceMs: options.debounceMs || 1000,
            autoDetect: options.autoDetect !== false,
            defaultLanguage: options.defaultLanguage,
            actualLanguage: options.actualLanguage || options.defaultLanguage,
            enabled: options.enabled !== false,
            isLanguageSupported: options.isLanguageSupported === true || options.isLanguageSupported === "true",
            ...options,
        };
        this.checkTimeout = null;
        this.currentErrors = [];
        this.isChecking = false;
        this.suggestionMenuEl = null;
        this.init();
    }

    init() {
        if (!this.options.enabled) return;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Subscribe to Summernote events to trigger checks
        this.$editor.on("summernote.change", () => this.debouncedCheck());
        this.$editor.on("summernote.keyup", (e) => {
            const key = e.originalEvent && e.originalEvent.key;
            if (key === "Enter" || key === " ") this.debouncedCheck();
        });
        this.$editor.on("summernote.paste", () => setTimeout(() => this.debouncedCheck(), 100));
        this.$editor.on("summernote.codeview.toggled", () => setTimeout(() => this.debouncedCheck(), 100));
        document.addEventListener("click", (e) => {
            if (this.suggestionMenuEl && !this.suggestionMenuEl.contains(e.target) && !e.target.classList.contains("language-error-marker")) {
                this.closeSuggestionMenu();
            }
        });
    }

    debouncedCheck() {
        if (this.checkTimeout) clearTimeout(this.checkTimeout);
        this.checkTimeout = setTimeout(() => this.checkText(), this.options.debounceMs);
    }

    getEditableEl() {
        const info = this.$editor.data("summernote");
        return info && info.layoutInfo && info.layoutInfo.editable ? info.layoutInfo.editable.get(0) : null;
    }

    getPlainText() {
        try {
            const html = this.$editor.summernote("code");
            const div = document.createElement("div");
            div.innerHTML = html;
            return (div.textContent || "").replace(/\s+/g, " ").trim();
        } catch (e) {
            return "";
        }
    }

    async checkText() {
        if (this.isChecking) return;
        const text = this.getPlainText();
        if (!text || text.length < 5) return;

        // Check if language is supported
        if (!this.options.isLanguageSupported) {
            const language = this.options.autoDetect ? this.options.actualLanguage : this.options.defaultLanguage;
            this.showError(`The language "${language}" is not supported by LanguageTool. Please enable auto-detect or use a supported language.`);
            return;
        }

        this.isChecking = true;
        this.showCheckingIndicator();
        try {
            const language = this.options.autoDetect ? "auto" : this.options.defaultLanguage;
            const response = await this.callLanguageToolAPI(text, language);
            if (response && response.result) this.processErrors(response.result);
        } catch (error) {
            const errorMsg = error && error.message ? error.message : "";
            const errorLower = errorMsg.toLowerCase();
            const isTimeout = errorLower.includes("timeout") || errorLower.includes("timed out");
            const isHtmlError = errorMsg.trim().startsWith("<!doctype") || errorMsg.trim().startsWith("<html");
            const isLanguageError = errorLower.includes("language") || errorLower.includes("unsupported");

            if (isLanguageError || (isTimeout && !this.options.autoDetect)) {
                const language = this.options.autoDetect ? this.options.actualLanguage : this.options.defaultLanguage;
                this.showError(`The language "${language}" is not supported by LanguageTool. Please enable auto-detect or use a supported language.`);
            } else if (isTimeout) {
                this.showError("Language checking timed out. The LanguageTool server may be unavailable or slow. Please try again.");
            } else if (isHtmlError) {
                this.showError("Language checking failed. Please check your LanguageTool server configuration.");
            } else {
                this.showError(errorMsg || "Language checking failed. Please try again.");
            }
        } finally {
            this.isChecking = false;
            this.hideCheckingIndicator();
        }
    }

    async callLanguageToolAPI(text, language) {
        const params = new URLSearchParams({ text, language });
        const response = await fetch(`tiki-ajax_services.php?controller=languagecheck&action=check_text&confirmForm=y`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: params,
        });
        if (!response.ok) {
            let detail = "";
            try {
                const ct = response.headers.get("content-type") || "";
                if (ct.includes("application/json")) {
                    const err = await response.json();
                    detail = (err && (err.message || err.error)) || JSON.stringify(err).slice(0, 200);
                } else {
                    detail = (await response.text()).slice(0, 200);
                }
            } catch (_) {
                // ignore parse errors
            }
            const message = detail || response.statusText || `Request failed`;
            throw new Error(message);
        }
        return await response.json();
    }

    processErrors(errors) {
        this.clearPreviousErrors();
        this.currentErrors = errors;
        if (!errors.length) {
            this.showNoErrorsMessage();
            return;
        }
        this.highlightErrors(errors);
    }

    // Highlighting logic
    highlightErrors(errors) {
        const editable = this.getEditableEl();
        if (!editable) return;
        let cursor = 0;

        const wrapAt = (start, length, index, replacements, message, rule) => {
            let remainingStart = start;
            let remainingLen = length;
            for (let i = 0; i < textNodes.length && remainingLen > 0; i++) {
                const node = textNodes[i];
                const nodeLen = node.nodeValue.length;
                if (remainingStart >= nodeLen) {
                    remainingStart -= nodeLen;
                    continue;
                }
                const take = Math.min(nodeLen - remainingStart, remainingLen);
                const range = document.createRange();
                range.setStart(node, remainingStart);
                range.setEnd(node, remainingStart + take);
                const span = document.createElement("span");
                span.className = "language-error-marker";
                span.dataset.errorIndex = String(index);
                span.dataset.errorReplacements = JSON.stringify(replacements || []);
                span.dataset.errorMessage = message || "";
                span.dataset.errorRule = (rule && (rule.description || rule.id)) || "";
                span.style.backgroundColor = "#ffebee";
                span.style.borderBottom = "2px wavy #f44336";
                span.style.cursor = "pointer";
                span.addEventListener("click", (e) => {
                    e.stopPropagation();
                    this.openSuggestionMenu(index, span);
                });
                range.surroundContents(span);
                // After wrapping, adjust textNodes cache for subsequent wraps
                // Refresh nodes within editable to keep indices accurate
                const newNodes = this.findTextNodes(editable);
                // Realign pointer: compute absolute position again
                const advanced = take;
                // Recompute remainingStart relative to new nodes start next iteration
                remainingStart = 0;
                remainingLen -= advanced;
                // Update reference array
                for (let j = 0; j < newNodes.length; j++) {
                    if (newNodes[j] === span.firstChild) {
                        // start inside this wrapped node
                        // continue from same node firstChild for any leftover
                        const restNodes = newNodes.slice(j);
                        textNodes.splice(0, textNodes.length, ...restNodes);
                        break;
                    }
                }
                i = -1; // restart loop over updated nodes subset
            }
        };

        const buildCum = () => {
            const nodes = this.findTextNodes(editable);
            const out = [];
            let acc = 0;
            for (const n of nodes) {
                const len = n.nodeValue ? n.nodeValue.length : 0;
                out.push({ node: n, start: acc, end: acc + len, len });
                acc += len;
            }
            return { cum: out, total: acc };
        };

        const wrapOnce = (start, length, index, replacements, message, rule) => {
            const { cum, total } = buildCum();
            let s = Math.max(0, Math.min(start, total));
            let l = Math.max(0, Math.min(length, Math.max(0, total - s)));
            if (l === 0) return;
            for (const entry of cum) {
                if (l <= 0) break;
                if (s >= entry.end) continue;
                if (s < entry.start) s = entry.start;
                const localStart = Math.max(0, s - entry.start);
                const available = entry.end - s;
                let take = Math.min(available, l);
                if (localStart > entry.len) continue;
                if (localStart + take > entry.len) take = Math.max(0, entry.len - localStart);
                if (take === 0) {
                    s = entry.end;
                    continue;
                }
                try {
                    const range = document.createRange();
                    range.setStart(entry.node, localStart);
                    range.setEnd(entry.node, localStart + take);
                    const span = document.createElement("span");
                    span.className = "language-error-marker";
                    span.dataset.errorIndex = String(index);
                    span.dataset.errorReplacements = JSON.stringify(replacements || []);
                    span.dataset.errorMessage = message || "";
                    span.dataset.errorRule = (rule && (rule.description || rule.id)) || "";
                    span.style.backgroundColor = "#ffebee";
                    span.style.borderBottom = "2px wavy #f44336";
                    span.style.cursor = "pointer";
                    span.addEventListener("click", (e) => {
                        e.stopPropagation();
                        this.openSuggestionMenu(index, span);
                    });
                    range.surroundContents(span);
                } catch (e) {
                    // Skip on boundary errors, proceed with next slice
                }
                s += take;
                l -= take;
            }
        };

        // Process from highest offset to lowest to keep earlier mappings stable
        const sorted = [...errors].map((e, i) => ({ e, i })).sort((a, b) => b.e.offset - a.e.offset);
        sorted.forEach(({ e, i }) => {
            wrapOnce(e.offset, e.length, i, e.replacements, e.message, e.rule);
        });
    }

    findTextNodes(element) {
        const textNodes = [];
        const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null, false);
        let node;
        while ((node = walker.nextNode())) {
            if (node.nodeValue && node.nodeValue.trim().length > 0) textNodes.push(node);
        }
        return textNodes;
    }

    openSuggestionMenu(errorIndex, markerEl) {
        // Create a small suggestion menu near the marker
        this.closeSuggestionMenu();
        const error = this.currentErrors[errorIndex];
        const menu = document.createElement("div");
        menu.className = "language-suggestion-menu card shadow-sm";
        menu.style.position = "absolute";
        menu.style.zIndex = "2000";
        menu.style.minWidth = "220px";
        menu.innerHTML = `
            <div class="card-body p-2">
                <div class="mb-2"><strong>Issue:</strong> ${this.escapeHtml(error.message || "")}</div>
                <div class="mb-2">
                    ${error.replacements && error.replacements.length ? "<div><strong>Suggestions:</strong></div>" : "<em>No suggestions</em>"}
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        ${(error.replacements || []).map((r) => `<button type="button" class="btn btn-sm btn-outline-primary" data-repl="${this.escapeHtml(r.value)}">${this.escapeHtml(r.value)}</button>`).join("")}
                    </div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-sm btn-secondary js-close">Close</button>
                </div>
            </div>
        `;
        document.body.appendChild(menu);
        // Position near marker
        const rect = markerEl.getBoundingClientRect();
        const top = window.scrollY + rect.bottom + 6;
        const left = window.scrollX + rect.left;
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
        // Bind button actions
        menu.querySelectorAll("button[data-repl]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const value = btn.getAttribute("data-repl") || "";
                this.applySuggestion(errorIndex, value);
            });
        });
        menu.querySelector(".js-close").addEventListener("click", () => this.closeSuggestionMenu());
        this.suggestionMenuEl = menu;
    }

    closeSuggestionMenu() {
        if (this.suggestionMenuEl && this.suggestionMenuEl.parentNode) {
            this.suggestionMenuEl.parentNode.removeChild(this.suggestionMenuEl);
        }
        this.suggestionMenuEl = null;
    }

    escapeHtml(str) {
        return String(str).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
    }

    applySuggestion(errorIndex, replacement) {
        // Replace the marked text with the selected suggestion and re-check
        const marker = this.getEditableEl().querySelector(`[data-error-index="${errorIndex}"]`);
        if (marker) {
            const textNode = document.createTextNode(replacement);
            marker.replaceWith(textNode);
            this.closeSuggestionMenu();
            // Re-run check to update
            this.debouncedCheck();
        }
    }

    // Feedback helpers
    getLayoutInfo() {
        return this.$editor.data("summernote").layoutInfo;
    }

    showCheckingIndicator() {
        const editor = this.getLayoutInfo().editor.get(0);
        if (!editor.querySelector(".language-checking-indicator")) {
            const indicator = document.createElement("div");
            indicator.className = "language-checking-indicator";
            indicator.innerHTML = `<small class="text-muted">Checking...</small>`;
            editor.appendChild(indicator);
        }
    }
    hideCheckingIndicator() {
        const editor = this.getLayoutInfo().editor.get(0);
        const indicator = editor.querySelector(".language-checking-indicator");
        if (indicator) indicator.remove();
    }
    showNoErrorsMessage() {
        const editor = this.getLayoutInfo().editor.get(0);
        if (!editor.querySelector(".language-no-errors")) {
            const el = document.createElement("div");
            el.className = "language-no-errors alert alert-success alert-dismissible fade show";
            el.innerHTML = `No language errors found! <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            editor.appendChild(el);
            setTimeout(() => el.remove(), 3000);
        }
    }
    showError(message) {
        const editor = this.getLayoutInfo().editor.get(0);
        if (!editor.querySelector(".language-error-message")) {
            const el = document.createElement("div");
            el.className = "language-error-message alert alert-danger alert-dismissible fade show";
            el.innerHTML = `${this.escapeHtml(message)} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            editor.appendChild(el);
            setTimeout(() => el.remove(), 5000);
        }
    }

    addToolbarButton() {}

    clearPreviousErrors() {
        const editable = this.getEditableEl();
        if (!editable) return;
        editable.querySelectorAll(".language-error-marker").forEach((n) => {
            // Unwrap: replace span with its text content before next pass
            const text = document.createTextNode(n.textContent);
            n.replaceWith(text);
        });
        this.closeSuggestionMenu();
    }

    destroy() {
        if (this.checkTimeout) clearTimeout(this.checkTimeout);
        this.clearPreviousErrors();
        this.hideCheckingIndicator();
        this.$editor.off("summernote.change summernote.keyup summernote.paste summernote.codeview.toggled");
        this.closeSuggestionMenu();
    }
}

export function setupLanguageCheckTrigger() {
    if (window.languageCheckTrigger) {
        return;
    }
    window.languageCheckTrigger = function (id) {
        const inst = $(`#${id}`).data("languageChecker");
        if (inst) inst.checkText();
    };
}

export function initializeLanguageCheck(target, options) {
    const lc = options.languageCheck || {};
    if (lc.enabled) {
        const languageChecker = new LanguageChecker(target, {
            enabled: true,
            debounceMs: Number(lc.debounceMs) || 1000,
            autoDetect: lc.autoDetect !== false,
            defaultLanguage: lc.defaultLanguage,
            isLanguageSupported: lc.isLanguageSupported === true || lc.isLanguageSupported === "true",
        });
        target.data("languageChecker", languageChecker);
    }
}

export default LanguageChecker;
