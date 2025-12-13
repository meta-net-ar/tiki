import figlet from "figlet";
export async function generateFiglet(path, width, domElId, text) {
    const filename = getFilename(path);
    let fontName = null;
    async function loadFont(fontName) {
        const res = await fetch(path);
        if (!res.ok) {
            throw new Error(tr("An error occurred while fetching the font"));
        }
        const fontData = await res.text();
        figlet.parseFont(fontName, fontData);
    }

    function getFilename(path) {
        // Handle both forward and backward slashes
        const parts = path.split(/[/\\]/);
        return parts.pop(); // Get the last element (filename)
    }

    if (filename.endsWith(".flf")) {
        fontName = filename.replace(".flf", "");
        try {
            await loadFont(fontName);
        } catch (e) {
            document.getElementById(domElId).textContent = tr("Error loading font");
            return;
        }
    }

    figlet.text(text, { font: fontName, width: width }, function (err, data) {
        const targetElement = document.getElementById(domElId);
        if (err) {
            if (targetElement) {
                targetElement.textContent = tr("Error generating text");
            }
            return;
        }
        if (targetElement) {
            targetElement.textContent = data;
        }
    });
}
