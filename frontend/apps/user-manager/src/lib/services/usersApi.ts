import type { UsersListResponse, SortColumn, SortDirection } from '../types/users';

export interface ListParams {
  search?: string;
  role_id?: number;
  offset?: number;
  limit?: number;
  sort?: SortColumn;
  direction?: SortDirection;
}

export const createUsersApi = (xhrPath: string) => ({
  async listUsers(params: ListParams = {}): Promise<UsersListResponse> {
    const qs = new URLSearchParams();
    if (params.search)    qs.set('search',    params.search);
    if (params.role_id)   qs.set('role_id',   String(params.role_id));
    if (params.offset)    qs.set('offset',    String(params.offset));
    if (params.limit)     qs.set('limit',     String(params.limit));
    if (params.sort)      qs.set('sort',      params.sort);
    if (params.direction) qs.set('direction', params.direction);

    const response = await fetch(`${xhrPath}users/list?${qs}`);
    return response.json();
  },

  async removeUser(userId: number): Promise<{ success: boolean; error?: string }> {
    const response = await fetch(`${xhrPath}users/remove`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `userID=${userId}`,
    });
    return response.json();
  },

  async setRoles(userId: number, roleIds: number[]): Promise<{ success: boolean; error?: string }> {
    const response = await fetch(`${xhrPath}users/set-roles`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `userID=${userId}&roles=${roleIds.join(',')}`,
    });
    return response.json();
  },
});
