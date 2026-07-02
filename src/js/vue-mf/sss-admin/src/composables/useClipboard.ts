import { ref } from "vue";

export function useClipboard() {
    const copySuccess = ref<string | null>(null);
    const copyError = ref<string | null>(null);

    async function copyToClipboard(text: string, key: string) {
        let ok = false;
        try {
            await navigator.clipboard.writeText(text);
            ok = true;
        } catch {
            // Clipboard API unavailable (non-HTTPS or permission denied) — fall back to execCommand
            try {
                const ta = document.createElement("textarea");
                ta.value = text;
                ta.style.position = "fixed";
                ta.style.opacity = "0";
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                ok = document.execCommand("copy"); /* deprecated, fallback only */
                document.body.removeChild(ta);
            } catch {
                // both methods failed
            }
        }
        if (ok) {
            copySuccess.value = key;
            copyError.value = null;
            setTimeout(() => {
                copySuccess.value = null;
            }, 2000);
        } else {
            copyError.value = key;
            setTimeout(() => {
                copyError.value = null;
            }, 4000);
        }
    }

    return { copySuccess, copyError, copyToClipboard };
}
