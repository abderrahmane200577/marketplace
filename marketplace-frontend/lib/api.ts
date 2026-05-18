const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api";

type ApiOptions = {
  token?: string;
  body?: unknown;
  method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE";
  headers?: HeadersInit;
};

async function apiRequest(path: string, options: ApiOptions = {}) {
  const response = await fetch(`${API_URL}${path}`, {
    method: options.method || "GET",
    headers: {
      Accept: "application/json",
      ...(options.body ? { "Content-Type": "application/json" } : {}),
      ...(options.token ? { Authorization: `Bearer ${options.token}` } : {}),
      ...options.headers,
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(data?.message || "API request failed");
  }

  return data;
}

export function apiGet(path: string, token?: string) {
  return apiRequest(path, { token });
}

export function apiPost(path: string, body: unknown, token?: string) {
  return apiRequest(path, { method: "POST", body, token });
}

export function apiPut(path: string, body: unknown, token?: string) {
  return apiRequest(path, { method: "PUT", body, token });
}

export function apiPatch(path: string, body: unknown, token?: string) {
  return apiRequest(path, { method: "PATCH", body, token });
}

export function apiDelete(path: string, token?: string) {
  return apiRequest(path, { method: "DELETE", token });
}
