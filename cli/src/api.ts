import { loadConfig, loadAuth } from "./config";

export async function apiRequest(endpoint: string, options: RequestInit = {}) {
  const config = loadConfig();
  const auth = loadAuth();

  const baseUrl = config.activeHost.replace(/\/$/, "");
  const url = `${baseUrl}/api/v1${endpoint.startsWith("/") ? endpoint : "/" + endpoint}`;

  const headers: Record<string, string> = {
    "Accept": "application/json",
    "Content-Type": "application/json",
    ...(options.headers as Record<string, string> || {}),
  };

  if (auth.token) {
    headers["Authorization"] = `Bearer ${auth.token}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const errText = await response.text();
    let message = `API Error ${response.status}: ${response.statusText}`;
    try {
      const json = JSON.parse(errText);
      if (json.message) message = json.message;
      else if (json.error) message = json.error;
    } catch {}
    throw new Error(message);
  }

  const contentType = response.headers.get("content-type");
  if (contentType && contentType.includes("application/json")) {
    return await response.json();
  }

  return await response.text();
}
