import { useEffect, useState, type ReactNode } from 'react';
import { apiClient } from '../../core/apiClient';
import { tokenStorage } from '../../core/tokenStorage';
import { AuthContext, type User } from './authContextTypes';

function extractUser(response: unknown): User | null {
  if (response && typeof response === 'object' && 'data' in (response as object)) {
    return (response as { data: User }).data;
  }
  return (response as User) ?? null;
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [status, setStatus] = useState<'unknown' | 'authenticated' | 'unauthenticated'>('unknown');
  const [user, setUser] = useState<User | null>(null);

  useEffect(() => {
    const token = tokenStorage.read();

    if (!token) {
      setStatus('unauthenticated');
      return;
    }

    apiClient
      .get('/auth/me')
      .then((response) => {
        setUser(extractUser(response));
        setStatus('authenticated');
      })
      .catch(() => {
        tokenStorage.clear();
        setStatus('unauthenticated');
      });
  }, []);

  async function login(login: string, password: string) {
    const response = (await apiClient.post('/auth/login', { login, password }, false)) as { token: string };
    tokenStorage.save(response.token);

    try {
      const me = await apiClient.get('/auth/me');
      setUser(extractUser(me));
    } catch {
      setUser(null);
    }

    setStatus('authenticated');
  }

  async function logout() {
    try {
      await apiClient.post('/auth/logout', {});
    } finally {
      tokenStorage.clear();
      setUser(null);
      setStatus('unauthenticated');
    }
  }

  return <AuthContext.Provider value={{ status, user, login, logout }}>{children}</AuthContext.Provider>;
}