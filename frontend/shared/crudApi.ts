export interface CrudApiResponse {
  success: boolean;
  id?: number;
  errors?: string[];
}

export const createCrudApi = (xhrPath: string) => ({
  async list<T>(endpoint: string): Promise<T[]> {
    const res = await fetch(`${xhrPath}${endpoint}`);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  },

  async save(endpoint: string, data: Record<string, unknown>): Promise<CrudApiResponse> {
    const body = new URLSearchParams();
    for (const [key, value] of Object.entries(data)) {
      if (Array.isArray(value)) {
        for (const item of value) {
          body.append(`${key}[]`, String(item));
        }
      } else if (value !== null && value !== undefined) {
        body.append(key, String(value));
      }
    }
    const res = await fetch(`${xhrPath}${endpoint}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  },

  async remove(endpoint: string, id: number): Promise<CrudApiResponse> {
    const res = await fetch(`${xhrPath}${endpoint}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `id=${id}`,
    });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    return res.json();
  },
});
