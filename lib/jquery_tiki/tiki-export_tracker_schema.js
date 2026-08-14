

function svgToDataUri(svgElement) {
    // Get the SVG's outer HTML (the full SVG as a string)
    const svgContent = svgElement.outerHTML;

    // Encode the SVG string in Base64
    const base64Encoded = btoa(svgContent);

    // Create a data URI with the MIME type image/svg+xml
    const dataUri = "data:image/svg+xml;base64," + base64Encoded;

    return dataUri;
}
// This JavaScript is used to generate an SVG file.
// Inspired by
// https://medium.com/@alan.nguyen2050/how-to-save-svg-html-to-svg-file-c2cde8d165f8
function embedSvgInImg() {
    const content = document.querySelector('.mermaid');
    const zoom = document.getElementById("svg-pan-zoom-controls");
    zoom.style.display = "none";
    // Get the SVG element
    const svgElement = document.getElementsByTagName("svg")[0];
    svgElement.style.display = "block";
    // Convert SVG to data URI
    const svgDataUri = svgToDataUri(svgElement);

    // Create an <img> element
    const imgElement = document.createElement("img");
    imgElement.src = svgDataUri;
    imgElement.alt = "Embedded SVG Image";
    imgElement.style.height = "100vh";
    imgElement.style.width = "100vw";

    // Optionally append the <img> to the DOM
    content.appendChild(imgElement);
    svgElement.style.display = "none";
}

function downloadTextPlain() {
    const svgRef = document.getElementById("mermaidText");
    const htmlStr = svgRef.value;
    const blob = new Blob([htmlStr], { type: "text/plain" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.setAttribute("download", "tracker.txt");
    a.setAttribute("href", url);
    a.style.display = "none";
    document.body.appendChild(a);
    a.click();
    URL.revokeObjectURL(url);
}

function downloadImg() {
    const zoomControls = document.querySelector("#svg-pan-zoom-controls");
    zoomControls.style.display = "none";

    const svgRef = document.getElementsByTagName("svg")[0];
    const htmlStr = svgRef.outerHTML;
    const blob = new Blob([htmlStr], { type: "image/svg+xml;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.setAttribute("download", "tracker.svg");
    a.setAttribute("href", url);
    a.style.display = "none";
    document.body.appendChild(a);
    a.click();
    URL.revokeObjectURL(url);
}

const exportType = document.querySelector("#export").value;
const buttonExport = document.querySelector("#buttonExport");
const svgFormat = document.querySelector("#svgFormat");
const textPlain = document.querySelector("#textPlain");
const imgSvg = document.querySelector("#imgSvg");
buttonExport.style.display = "none";

window.addEventListener('load', () => {
    if (exportType !== 'svgFormat') {
        buttonExport.style.display = 'block';
    }
    if (exportType == 'imgSvg') {
        embedSvgInImg();
    }
});

buttonExport.addEventListener("click", () => {
    if (textPlain.checked) {
        downloadTextPlain();
    } else if (imgSvg.checked) {
        downloadImg();
    }
});
svgFormat.addEventListener("change", () => {
    if (svgFormat.checked) {
        buttonExport.style.display = "none";
    }
});

textPlain.addEventListener("change", () => {
    if (textPlain.checked) {
        buttonExport.style.display = "block";
    }
});

imgSvg.addEventListener("change", () => {
    if (imgSvg.checked) {
        buttonExport.style.display = "block";
    }
});
