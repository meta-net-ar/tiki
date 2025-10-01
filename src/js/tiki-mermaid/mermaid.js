import svgPanZoom from "svg-pan-zoom";
import mermaid from "mermaid/dist/mermaid.esm.mjs"; //from "mermaid" will resolve to mermaid-core.esm.mjs, which currently doesn't work - benoitg-2025-03-14.

export default function handleMermaid() {
    mermaid.initialize({
        startOnLoad: false,
        maxTextSize: 200000,
    });

    const drawDiagram = async function (element) {
        const containMermaidData = element.parentElement;
        const graphDefinition = containMermaidData.querySelector(".code").value;
        const { svg } = await mermaid.render("graphDiv", graphDefinition);
        element.innerHTML = svg.replace(/[ ]*max-width:[ 0-9\.]*px;/i, "");
        return element.querySelector("svg");
    };

    const renderDiagram = async (element) => {
        try {
            let svgElement = await drawDiagram(element);
            svgElement.style.height = "60vh";
            svgElement.style.maxWidth = "100%";
            const mermaidPanZoom = svgPanZoom(svgElement, {
                zoomEnabled: true,
                controlIconsEnabled: true,
                fit: true,
                center: true,
            });
        } catch (e) {
            let errorDiv = document.createElement("div");
            errorDiv.className = "alert alert-danger";
            errorDiv.role = "alert";
            errorDiv.innerHTML = e;
            element.prepend(errorDiv);
        }
    };
    const elements = document.querySelectorAll(".mermaid");
    window.addEventListener("load", async () => {
        for (let i = 0; i < elements.length; i++) {
            await renderDiagram(elements[i]);
        }
    });
}
