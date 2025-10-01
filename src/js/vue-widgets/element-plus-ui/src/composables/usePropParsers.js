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
        try {
            return typeof val === "string" ? JSON.parse(val) : val;
        } catch {
            return val;
        }
    };

    return { normalize, parseValue };
}
