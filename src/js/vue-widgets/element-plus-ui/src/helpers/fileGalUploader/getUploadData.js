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

    // serializeArray() repeats multi-value fields (cat_categories[], cat_managed[]);
    // a flat assign keeps only the last and silently drops the selection.
    $("form#file_0")
        .serializeArray()
        .forEach((item) => {
            if (item.name.endsWith("[]")) {
                if (!Array.isArray(result[item.name])) {
                    result[item.name] = [];
                }
                result[item.name].push(item.value);
            } else {
                result[item.name] = item.value;
            }
        });

    return result;
}
