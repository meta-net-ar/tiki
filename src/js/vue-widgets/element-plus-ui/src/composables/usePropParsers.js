export function usePropParsers() {
    const normalize = (val, fallback = false) => {
        if (val === undefined || val === null) return fallback;
        if (typeof val === "boolean") return val;
        try {
            return JSON.parse(val);
        } catch {
            return val;
        }
    };

    const parseValue = (val) => {
        // Ensure numeric strings remain unparsed to avoid unintended numeric conversion
        if (!isNaN(val)) return val;
        try {
            return typeof val === "string" ? JSON.parse(val) : val;
        } catch {
            return val;
        }
    };

    return { normalize, parseValue };
}
