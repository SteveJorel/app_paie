import { useAuth } from '../features/auth/useAuth';

export default function DashboardPage() {
  const { user } = useAuth();

  return (
    <div>
      <h1>Bienvenue{user?.name ? `, ${user.name as string}` : ''}</h1>
      <p>Rôle connecté : {(user?.role as string) ?? 'inconnu'}</p>
    </div>
  );
}