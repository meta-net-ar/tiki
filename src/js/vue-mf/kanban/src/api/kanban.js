import ky from "ky";
import { stringify } from "picoquery";
import store from "../store";

//Strip the last fragment of the path, and appends the api path.
const baseUrl = location.protocol + "//" + location.host + location.pathname.replace(/\/[^/]*$/, "") + "/api/";

const api = ky.create({
    prefixUrl: baseUrl,
});

const authHeaders = () => ({
    Authorization: `Bearer ${store.getters.getAccessToken}`,
});

async function request(method, url, options = {}) {
    try {
        const data = await api(url, { method, ...options }).json();
        return { data };
    } catch (err) {
        if (err.response) {
            try {
                err.response.data = await err.response.json();
            } catch {
                err.response.data = {};
            }
        }
        throw err;
    }
}

// Add api method here
export default {
    createBoard: function ({ trackerId, itemId }, payload) {
        return request("post", `trackers/${trackerId}/`, {
            body: stringify(payload),
            headers: authHeaders(),
        });
    },
    createItem: function ({ trackerId }, payload) {
        // Sample
        // fields[fieldPermName]=value&fields[anotherFieldPermName]=anotherValue
        return request("post", `trackers/${trackerId}/items`, {
            body: stringify(payload, { encode: false }),
            headers: authHeaders(),
        });
    },
    getItem: function ({ trackerId, itemId }, payload) {
        return request("get", `trackers/${trackerId}/items/${itemId}`, {
            searchParams: payload,
            headers: authHeaders(),
        });
    },
    setItem: function ({ trackerId, itemId }, payload) {
        return request("post", `trackers/${trackerId}/items/${itemId}`, {
            body: stringify(payload, { encode: false }),
            headers: authHeaders(),
        });
    },
    deleteItem: function ({ trackerId, itemId }) {
        return request("delete", `trackers/${trackerId}/items/${itemId}`, {
            headers: authHeaders(),
        });
    },
    getField: function ({ trackerId, fieldId }, payload) {
        return request("get", `trackers/${trackerId}/fields/${fieldId}`, {
            searchParams: payload,
            headers: authHeaders(),
        });
    },
    setField: function ({ trackerId, fieldId }, payload) {
        return request("post", `trackers/${trackerId}/fields/${fieldId}`, {
            body: stringify(payload),
            headers: authHeaders(),
        });
    },
    deleteField: function ({ trackerId, fieldId }) {
        return request("delete", `trackers/${trackerId}/fields/${fieldId}`, {
            headers: authHeaders(),
        });
    },
    getUsers: function () {
        return request("get", `users`, {
            headers: authHeaders(),
        });
    },
};
