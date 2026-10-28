import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { useAuth } from './useAuth';

export default function ProtectedRoute({
  children,
  allowedRoles,
}: {
  children: ReactNode;
  allowedRoles?: string[];
}) {
  const { status, user } = useAuth();

  if (status === 'unknown') {
    return <div className="centered">Chargement...</div>;
  }

  if (status === 'unauthenticated') {
    return <Navigate to="/login" replace />;
  }

  if (allowedRoles && (!user?.role || !allowedRoles.includes(user.role as string))) {
    return <div className="centered">Accès refusé pour ce rôle.</div>;
  }

  return <>{children}</>;
}