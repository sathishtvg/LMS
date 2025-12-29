// resources/js/lib/apiClient.js
// Lightweight fetch wrapper for Laravel (Sanctum cookie auth + optional token auth)

function getCookie(name) {
  const m = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  return m ? decodeURIComponent(m[2]) : null;
}

function defaultHeaders(isJson = true) {
  const headers = {};

  // CSRF: Laravel sets XSRF-TOKEN cookie; Sanctum expects X-XSRF-TOKEN for SPA requests.
  const xsrf = getCookie('XSRF-TOKEN');
  if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

  // For classic blade meta csrf-token (also OK to include)
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (meta && meta.content) headers['X-CSRF-TOKEN'] = meta.content;

  // Tenant support (optional fallback)
  const tenantCode = localStorage.getItem('tenant_code');
  if (tenantCode) headers['X-Tenant-Code'] = tenantCode;

  // Optional token auth (mobile / postman etc.)
  const token = localStorage.getItem('token');
  if (token) headers['Authorization'] = `Bearer ${token}`;

  if (isJson) headers['Accept'] = 'application/json';
  return headers;
}

async function apiRequest(url, { method = 'GET', data = null, json = true } = {}) {
  const opts = {
    method,
    credentials: 'include', // critical for Sanctum cookie auth
    headers: {
      ...defaultHeaders(json),
    },
  };

  if (data !== null) {
    if (json) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(data);
    } else {
      // FormData / upload
      opts.body = data;
    }
  }

  const res = await fetch(url, opts);

  // If backend returns HTML (e.g., 419/500), surface it clearly
  const contentType = res.headers.get('content-type') || '';
  const isJsonResp = contentType.includes('application/json');

  if (!res.ok) {
    if (isJsonResp) {
      const err = await res.json().catch(() => ({}));
      const msg = err?.message || `Request failed (${res.status})`;
      const e = new Error(msg);
      e.status = res.status;
      e.payload = err;
      throw e;
    } else {
      const text = await res.text();
      const e = new Error(`Request failed (${res.status})`);
      e.status = res.status;
      e.payload = text;
      throw e;
    }
  }

  if (res.status === 204) return null;
  return isJsonResp ? res.json() : res.text();
}

export const apiFetch = (url) => apiRequest(url, { method: 'GET' });
export const apiSend = (url, data, method = 'POST') => apiRequest(url, { method, data, json: true });

export const apiGet = apiFetch;
export const apiPost = (url, data) => apiRequest(url, { method: 'POST', data, json: true });
export const apiPut = (url, data) => apiRequest(url, { method: 'PUT', data, json: true });
export const apiPatch = (url, data) => apiRequest(url, { method: 'PATCH', data, json: true });
export const apiDelete = (url) => apiRequest(url, { method: 'DELETE' });

// Upload helper: accepts FormData
export const apiUpload = (url, formData, method = 'POST') => apiRequest(url, { method, data: formData, json: false });

export default apiRequest;
