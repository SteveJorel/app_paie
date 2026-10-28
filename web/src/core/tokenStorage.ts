const KEY = 'auth_token';

export const tokenStorage = {
  save(token: string) {
    localStorage.setItem(KEY, token);
  },
  read(): string | null {
    return localStorage.getItem(KEY);
  },
  clear() {
    localStorage.removeItem(KEY);
  },
};