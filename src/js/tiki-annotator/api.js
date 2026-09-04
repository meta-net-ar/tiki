const baseURL = "tiki-ajax_services.php?controller=annotation&action=";

const AnnotationAPI = {
    create: `${baseURL}create`,
    update: `${baseURL}update`,
    delete: `${baseURL}destroy`,
    search: `${baseURL}search`,
};

let currentTicket = "";

export function setTicket(ticket) {
    currentTicket = ticket ? String(ticket) : "";
}

export function getTicket() {
    return currentTicket;
}

function readError(payload, status) {
    if (payload && typeof payload === "object") {
        const message = payload.message || payload.errortitle;
        if (message) {
            return String(message);
        }
    }

    if (typeof payload === "string" && payload.trim()) {
        return payload.trim();
    }

    return `HTTP ${status}`;
}

function buildBody(payload) {
    const body = new URLSearchParams();

    Object.entries(payload || {}).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }
        body.append(key, typeof value === "object" ? JSON.stringify(value) : String(value));
    });

    if (currentTicket) {
        body.append("ticket", currentTicket);
    }

    return body;
}

async function apiRequest(url, payload, method = "POST") {
    const options = {
        method,
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
        },
    };

    if (method !== "GET") {
        options.headers["Content-Type"] = "application/x-www-form-urlencoded";
        options.body = buildBody(payload);
    }

    const response = await fetch(url, options);
    const text = await response.text();

    let data = null;

    if (text) {
        try {
            data = JSON.parse(text);
        } catch {
            data = text;
        }
    }

    if (!response.ok) {
        throw new Error(readError(data, response.status));
    }

    if (data && typeof data === "object" && data.ticket) {
        setTicket(data.ticket);
    }

    return data;
}

export async function saveAnnotationBackend(payload) {
    return apiRequest(AnnotationAPI.create, payload);
}

export async function updateAnnotationBackend(payload) {
    return apiRequest(AnnotationAPI.update, payload);
}

export async function deleteAnnotationBackend(threadId) {
    return apiRequest(AnnotationAPI.delete, { threadId });
}

export async function loadAnnotations(objectUri, transformFn) {
    const searchUrl = `${AnnotationAPI.search}&uri=${encodeURIComponent(objectUri)}`;

    const data = await apiRequest(searchUrl, null, "GET");

    return transformFn ? transformFn(data) : data;
}
