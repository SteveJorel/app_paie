import { useEffect, useState, type ReactNode } from 'react';
import { apiClient } from '../../core/apiClient';
import { tokenStorage } from '../../core/tokenStorage';
import { AuthContext, type User } from './authContextTypes';

// Le backend renvoie { user: {...} } (login et /auth/me) ; on accepte aussi { data: {...} }.
function extractUser(response: unknown): User | null {
  if (response && typeof response === 'object') {
    const body = response as { user?: User; data?: User };
    if (body.user && typeof body.user === 'object') return body.user;
    if (body.data && typeof body.data === 'object') return body.data;
  }
  return null;
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
        const current = extractUser(response);

        if (!current) {
          tokenStorage.clear();
          setStatus('unauthenticated');
          return;
        }

        setUser(current);
        setStatus('authenticated');
      })
      .catch(() => {
        tokenStorage.clear();
        setStatus('unauthenticated');
      });
  }, []);

  async function login(login: string, password: string) {
    const response = (await apiClient.post('/auth/login', { login, password }, false)) as {
      token: string;
      user?: User;
    };
    tokenStorage.save(response.token);

    // La réponse de connexion contient déjà l'utilisateur ; /auth/me sert de secours.
    let current = extractUser(response);

    if (!current) {
      try {
        current = extractUser(await apiClient.get('/auth/me'));
      } catch {
        current = null;
      }
    }

    setUser(current);
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