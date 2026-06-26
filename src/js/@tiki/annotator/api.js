const baseURL = "tiki-ajax_services.php?controller=annotation&action=";

const AnnotationAPI = {
  create: `${baseURL}create`,
  update: `${baseURL}update`,
  delete: `${baseURL}destroy`,
  search: `${baseURL}search`
};

async function apiRequest(url, payload, method = 'POST') {
  try {

    const options = {
      method,
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      }
    };

    if (method !== 'GET' && payload) {
      options.body = JSON.stringify(payload);
    }

    const response = await fetch(url, options);

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`HTTP ${response.status}: ${text}`);
    }

    return await response.json();

  } catch (err) {
    throw err;
  }
}

export async function saveAnnotationBackend(payload) {
  const data = await apiRequest(AnnotationAPI.create, payload);
  return data;
}

export async function updateAnnotationBackend(payload) {
  return apiRequest(AnnotationAPI.update, payload);
}

export async function deleteAnnotationBackend(threadId) {
  return apiRequest(AnnotationAPI.delete, { threadId });
}

export async function loadAnnotations(objectUri, transformFn) {

  const searchUrl = `${AnnotationAPI.search}&uri=${encodeURIComponent(objectUri)}`;

  const data = await apiRequest(searchUrl, null, 'GET');

  const annotations = transformFn? transformFn(data) : data;

  return annotations;
}
