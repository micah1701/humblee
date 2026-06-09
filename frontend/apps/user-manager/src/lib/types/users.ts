export interface Role {
  id: number;
  name: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  username: string;
  roles: Role[];
  last_login: string;
  logins: number;
  is_current_user: boolean;
}

export interface UsersListResponse {
  users: User[];
  total: number;
  offset: number;
  limit: number;
}

export type SortColumn = 'name' | 'email' | 'username' | 'last_login' | 'logins';
export type SortDirection = 'asc' | 'desc';

export interface UsersConfig {
  xhrPath: string;
  roles: Role[];
  isDeveloper: boolean;
  currentUserId: number;
}
