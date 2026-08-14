// Lightweight language checking for plain textareas (no Summernote)
// - Debounced checks
// - Inline overlay highlighting (non-intrusive)
// - Suggestion popup to apply replacements

function debounce(fn, wait) {
    let t = null;
    return function (...args) {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function escapeHtml(str) {
    return String(str).replace(/[&<>"]/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[c]);
}

function buildOverlay(container, textarea) {
    const overlay = document.createElement("div");
    overlay.className = "tiki-language-overlay";
    const cs = window.getComputedStyle(textarea);
    overlay.style.position = "absolute";
    overlay.style.top = textarea.offsetTop + "px";
    overlay.style.left = textarea.offsetLeft + "px";
    overlay.style.width = textarea.offsetWidth + "px";
    overlay.style.height = textarea.offsetHeight + "px";
    overlay.style.pointerEvents = "none";
    overlay.style.whiteSpace = "pre-wrap";
    overlay.style.overflow = "hidden";
    overlay.style.padding = cs.padding;
    overlay.style.borderRadius = cs.borderRadius;
    overlay.style.font = cs.font;
    overlay.style.lineHeight = cs.lineHeight;
    overlay.style.color = "transparent"; // text comes from textarea; overlay only shows underlines
    overlay.style.zIndex = (parseInt(cs.zIndex || "1", 10) + 1).toString();
    container.style.position = container.style.position || "relative";
    container.appendChild(overlay);
    return overlay;
}

function renderOverlay(overlay, text, errors) {
    // Build HTML with spans for error ranges; make spans clickable via pointer-events
    let html = "";
    let cursor = 0;
    const add = (s) => {
        html += escapeHtml(s);
    };
    const addSpan = (s, idx) => {
        html +=
            '<span class="tiki-language-marker" data-idx="' +
            idx +
            '" style="pointer-events:auto;background:rgba(244,67,54,0.12);border-bottom:2px wavy #f44336;cursor:pointer;">' +
            escapeHtml(s) +
            "</span>";
    };
    const sorted = [...errors].map((e, i) => ({ e, i })).sort((a, b) => a.e.offset - b.e.offset);
    for (const { e, i } of sorted) {
        const start = Math.max(0, Math.min(e.offset, text.length));
        const end = Math.max(start, Math.min(e.offset + e.length, text.length));
        if (start > cursor) add(text.slice(cursor, start));
        addSpan(text.slice(start, end), i, e);
        cursor = end;
    }
    if (cursor < text.length) add(text.slice(cursor));
    overlay.innerHTML = html || "";
}

function createSuggestionMenu(error, onApply) {
    const menu = document.createElement("div");
    menu.className = "tiki-language-menu card shadow-sm";
    menu.style.position = "absolute";
    menu.style.zIndex = "2000";
    menu.style.minWidth = "220px";
    menu.style.pointerEvents = "auto";
    const suggestions = (error.replacements || []).map((r) => r.value).slice(0, 10);
    menu.innerHTML = `
        <div class="card-body p-2">
            <div class="mb-2"><strong>Issue:</strong> ${escapeHtml(error.message || "")}</div>
            <div class="mb-2">
                ${suggestions.length ? "<div><strong>Suggestions:</strong></div>" : "<em>No suggestions</em>"}
                <div class="d-flex flex-wrap gap-1 mt-1">
                    ${suggestions.map((v) => `<button type="button" class="btn btn-sm btn-outline-primary" data-repl="${escapeHtml(v)}">${escapeHtml(v)}</button>`).join("")}
                </div>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-sm btn-secondary js-close">Close</button>
            </div>
        </div>`;
    menu.querySelectorAll("button[data-repl]").forEach((btn) => {
        btn.addEventListener("click", () => onApply(btn.getAttribute("data-repl") || ""));
    });
    menu.querySelector(".js-close").addEventListener("click", () => menu.remove());
    return menu;
}

async function checkAPI(text, language) {
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
                const candidate = err && (err.message || err.error);
                detail = candidate || JSON.stringify(err).slice(0, 200);
            } else {
                detail = (await response.text()).slice(0, 200);
            }
        } catch (_) {}
        throw new Error(detail || response.statusText || "Request failed");
    }
    return await response.json();
}

export default function initTextareaLanguageCheck(selector, opts = {}) {
    const textarea = typeof selector === "string" ? document.querySelector(selector) : selector;
    if (!textarea) return;
    const container = textarea.parentElement || document.body;
    const overlay = buildOverlay(container, textarea);
    let errors = [];

    const options = {
        debounceMs: Number(opts.debounceMs) || 1000,
        autoDetect: opts.autoDetect !== false,
        defaultLanguage: opts.defaultLanguage,
        isLanguageSupported: opts.isLanguageSupported === true || opts.isLanguageSupported === "true",
        enabled: opts.enabled !== false,
    };

    const syncOverlayBox = () => {
        overlay.style.top = textarea.offsetTop + "px";
        overlay.style.left = textarea.offsetLeft + "px";
        overlay.style.width = textarea.offsetWidth + "px";
        overlay.style.height = textarea.offsetHeight + "px";
        overlay.scrollTop = textarea.scrollTop;
        overlay.scrollLeft = textarea.scrollLeft;
    };
    const updateOverlay = () => {
        if (!options.enabled) {
            overlay.innerHTML = "";
            return;
        }
        const text = textarea.value || "";
        renderOverlay(overlay, text, errors);
        overlay.querySelectorAll(".tiki-language-marker").forEach((el) => {
            el.addEventListener(
                "click",
                (e) => {
                    e.stopPropagation();
                    const idx = Number(el.getAttribute("data-idx")) || 0;
                    const err = errors[idx];
                    if (!err) return;
                    const menu = createSuggestionMenu(err, (replacement) => {
                        const start = err.offset;
                        const end = err.offset + err.length;
                        const before = (textarea.value || "").slice(0, start);
                        const after = (textarea.value || "").slice(end);
                        textarea.value = before + replacement + after;
                        menu.remove();
                        debouncedCheck();
                    });
                    document.body.appendChild(menu);
                    const rect = el.getBoundingClientRect();
                    menu.style.top = window.scrollY + rect.bottom + 6 + "px";
                    menu.style.left = window.scrollX + rect.left + "px";
                },
                { once: true }
            );
        });
        syncOverlayBox();
    };

    const showError = (message) => {
        // Remove any existing error message
        const existing = container.querySelector(".language-error-message");
        if (existing) {
            existing.remove();
        }
        // Create and display error alert
        const el = document.createElement("div");
        el.className = "language-error-message alert alert-danger alert-dismissible fade show";
        el.innerHTML = `${escapeHtml(message)} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        container.insertBefore(el, textarea);
        setTimeout(() => el.remove(), 5000);
    };

    const doCheck = async () => {
        const text = textarea.value || "";
        if (!text || text.length < 5) {
            errors = [];
            updateOverlay();
            return;
        }

        // Check if language is supported
        if (!options.isLanguageSupported) {
            const language = options.autoDetect ? "auto" : options.defaultLanguage;
            showError(`The language "${language}" is not supported by LanguageTool. Please enable auto-detect or use a supported language.`);
            errors = [];
            updateOverlay();
            return;
        }

        try {
            const lang = options.autoDetect ? "auto" : options.defaultLanguage;
            const res = await checkAPI(text, lang);
            errors = Array.isArray(res && res.result) ? res.result : [];
        } catch (e) {
            const errorMsg = e && e.message ? e.message : "";
            const errorLower = errorMsg.toLowerCase();
            const isTimeout = errorLower.includes("timeout") || errorLower.includes("timed out");
            const isLanguageError = errorLower.includes("language") || errorLower.includes("unsupported");

            if (isLanguageError || (isTimeout && !options.autoDetect)) {
                const language = options.autoDetect ? "auto" : options.defaultLanguage;
                showError(`The language "${language}" is not supported by LanguageTool. Please enable auto-detect or use a supported language.`);
            }
            errors = [];
        } finally {
            updateOverlay();
        }
    };
    const debouncedCheck = debounce(doCheck, options.debounceMs);

    // Events
    textarea.addEventListener("input", debouncedCheck);
    textarea.addEventListener("paste", () => setTimeout(debouncedCheck, 100));
    textarea.addEventListener("scroll", syncOverlayBox);
    window.addEventListener("resize", syncOverlayBox);

    // Initial
    debouncedCheck();
}
