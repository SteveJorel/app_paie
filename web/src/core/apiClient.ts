import { API_BASE_URL } from './apiConfig';
import { ApiException } from './apiException';
import { tokenStorage } from './tokenStorage';

type Json = Record<string, unknown>;

function headers(withAuth: boolean, extra?: Record<string, string>): HeadersInit {
  const base: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    ...extra,
  };

  if (withAuth) {
    const token = tokenStorage.read();
    if (token) base['Authorization'] = `Bearer ${token}`;
  }

  return base;
}

async function handle(response: Response) {
  const text = await response.text();
  const decoded = text ? JSON.parse(text) : null;

  if (response.ok) return decoded;

  const message =
    decoded && typeof decoded === 'object' && 'message' in decoded
      ? String((decoded as Json).message)
      : `Une erreur est survenue (${response.status}).`;

  const errors =
    decoded && typeof decoded === 'object' && 'errors' in decoded
      ? ((decoded as Json).errors as Record<string, string[]>)
      : undefined;

  throw new ApiException(response.status, message, errors);
}

export const apiClient = {
  async get(path: string, withAuth = true) {
    const response = await fetch(`${API_BASE_URL}${path}`, { headers: headers(withAuth) });
    return handle(response);
  },

  async post(path: string, body: Json, withAuth = true) {
    const response = await fetch(`${API_BASE_URL}${path}`, {
      method: 'POST',
      headers: headers(withAuth),
      body: JSON.stringify(body),
    });
    return handle(response);
  },

  async patch(path: string, body: Json, withAuth = true) {
    const response = await fetch(`${API_BASE_URL}${path}`, {
      method: 'PATCH',
      headers: headers(withAuth),
      body: JSON.stringify(body),
    });
    return handle(response);
  },
};