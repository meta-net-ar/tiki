export default function showMessage(message, type, closable, duration) {
    const messageElement = document.createElement("el-message");
    messageElement.setAttribute("message", message);
    if (type) messageElement.setAttribute("type", type);
    if (closable) messageElement.setAttribute("closable", closable);
    if (!isNaN(duration)) messageElement.setAttribute("duration", duration);
    document.body.appendChild(messageElement);

    messageElement.addEventListener("close", () => {
        document.body.removeChild(messageElement);
    });
}
