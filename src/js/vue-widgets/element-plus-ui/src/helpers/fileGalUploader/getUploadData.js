import { ElMessage } from "element-plus";
import adjustImageSize from "./adjustImageSize";
import fileToBase64 from "./fileToBase64";

export default async function (file, maxWidth, maxHeight) {
    const result = {
        name: file.name,
        type: file.type,
        size: file.size,
    };

    try {
        const base64 = await fileToBase64(file);
        if (maxWidth && maxHeight && file.type.includes("image")) {
            const resized = await adjustImageSize(base64, maxWidth, maxHeight, file.type);
            result.data = resized.replace(/^data:image\/\w+;base64,/, "");
        } else {
            result.data = base64.replace(/^data:.*?;base64,/, "");
        }
    } catch (error) {
        ElMessage.error("Failed to get file data");
    }

    const form = $("form#file_0").serializeArray();
    form.forEach((item) => {
        result[item.name] = item.value;
    });

    return result;
}
