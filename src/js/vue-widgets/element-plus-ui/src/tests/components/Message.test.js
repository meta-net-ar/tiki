import { render } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import Message from "../../components/Message/Message.vue";
import { ElMessage } from "element-plus";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElMessage: vi.fn(),
    };
});

describe("Message", () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    test("renders correctly with some basic props", () => {
        const givenProps = {
            message: "Hello, World!",
            type: "success",
        };

        render(Message, { props: givenProps });

        expect(ElMessage).toHaveBeenCalledWith({
            message: givenProps.message,
            type: givenProps.type,
            duration: 3000,
            showClose: false,
            onClose: expect.any(Function),
        });
    });

    test("renders correctly with all props", () => {
        const givenProps = {
            message: "Hello, World!",
            type: "success",
            duration: "5000",
            closable: "true",
        };

        render(Message, { props: givenProps });

        expect(ElMessage).toHaveBeenCalledWith({
            message: givenProps.message,
            type: givenProps.type,
            duration: 5000,
            showClose: true,
            onClose: expect.any(Function),
        });
    });

    test("emits the 'close' event when the message is closed", () => {
        const givenProps = {
            message: "Hello, World!",
            type: "success",
            _emit: vi.fn(),
        };

        render(Message, { props: givenProps });

        const onClose = ElMessage.mock.calls[0][0].onClose;
        onClose();

        expect(givenProps._emit).toHaveBeenCalledWith("close");
    });
});
