import { afterEach, describe, expect, test } from "vitest";
import showMessage from "../../utils/showMessage";

describe("showMessage", () => {
    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("renders the message element with only the message argument provided", () => {
        const givenMessage = "Hello, World!";

        showMessage(givenMessage);

        const messageElement = document.querySelector("el-message");
        expect(messageElement).to.exist;
        expect(messageElement.getAttribute("message")).toBe(givenMessage);
        expect(messageElement.getAttribute("type")).toBeNull();
        expect(messageElement.getAttribute("closable")).toBeNull();
        expect(messageElement.getAttribute("duration")).toBeNull();
    });

    test("renders the message element with all arguments provided", () => {
        const givenMessage = "Hello, World!";
        const givenType = "success";
        const givenClosable = "true";
        const givenDuration = 0;

        showMessage(givenMessage, givenType, givenClosable, givenDuration);

        const messageElement = document.querySelector("el-message");
        expect(messageElement).to.exist;
        expect(messageElement.getAttribute("message")).toBe(givenMessage);
        expect(messageElement.getAttribute("type")).toBe(givenType);
        expect(messageElement.getAttribute("closable")).toBe(givenClosable);
        expect(messageElement.getAttribute("duration")).toBe(givenDuration.toString());
    });

    test("removes the message element when it is closed", () => {
        const givenMessage = "Hello, World!";

        showMessage(givenMessage);

        const messageElement = document.querySelector("el-message");
        messageElement.dispatchEvent(new Event("close"));

        expect(document.querySelector("el-message")).toBeNull();
    });
});
