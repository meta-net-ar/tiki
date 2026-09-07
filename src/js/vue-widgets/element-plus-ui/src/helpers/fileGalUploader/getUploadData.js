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
