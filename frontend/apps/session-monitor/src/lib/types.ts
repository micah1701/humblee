export interface SessionMonitorConfig {
  XHR_PATH: string;
  checkIntervalMs: number;
}

export interface SessionCheckResponse {
  loggedIn: boolean;
  hmacKey?: string;
  hmacToken?: string;
}

export interface SessionLoginResponse {
  success: boolean;
  message?: string;
}
