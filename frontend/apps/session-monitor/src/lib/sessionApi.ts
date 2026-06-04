import type { SessionCheckResponse, SessionLoginResponse } from './types';

export async function checkSession(xhrPath: string): Promise<SessionCheckResponse> {
  const response = await fetch(`${xhrPath}session_check`);
  return response.json();
}

export async function loginSession(
  xhrPath: string,
  username: string,
  password: string,
  hmacToken: string,
  hmacKey: string,
  remember: boolean
): Promise<SessionLoginResponse> {
  const body = new URLSearchParams({
    username,
    password,
    hmac_token: hmacToken,
    hmac_key: hmacKey,
  });
  if (remember) {
    body.set('remember', '1');
  }
  const response = await fetch(`${xhrPath}session_login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: body.toString(),
  });
  return response.json();
}
