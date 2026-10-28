import { createContext } from 'react';

export type User = Record<string, unknown> & { role?: string; name?: string };
export type AuthStatus = 'unknown' | 'authenticated' | 'unauthenticated';

export interface AuthContextValue {
  status: AuthStatus;
  user: User | null;
  login: (login: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

export const AuthContext = createContext<AuthContextValue | undefined>(undefined);