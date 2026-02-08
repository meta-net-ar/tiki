import { ElMessage } from "element-plus";
import fileToBase64 from "./fileToBase64";

export default async function (file) {
    const result = {
        name: file.name,
        type: file.type,
        size: file.size,
    };

    try {
        const base64 = await fileToBase64(file);
        result.data = base64.replace(/^data:.*?;base64,/, "");
    } catch (error) {
        ElMessage.error("Failed to get file data");
    }

    const form = $("form#file_0").serializeArray();
    form.forEach((item) => {
        result[item.name] = item.value;
    });

    return result;
}
