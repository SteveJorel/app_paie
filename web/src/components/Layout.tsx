import type { ReactNode } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useAuth } from '../features/auth/useAuth';

export default function Layout({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth();
  const location = useLocation();
  const role = user?.role as string | undefined;

  const navItems: { to: string; label: string }[] = [{ to: '/', label: 'Accueil' }];

  if (role === 'admin') {
    navItems.push({ to: '/admin/suppliers', label: 'Fournisseurs' });
  }
  if (role === 'admin' || role === 'finance') {
    navItems.push({ to: '/admin/commission-rules', label: 'Commissions' });
    navItems.push({ to: '/admin/reports', label: 'Rapprochement' });
    navItems.push({ to: '/admin/transactions', label: 'Transactions' });
  }
  if (role === 'admin') {
    navItems.push({ to: '/admin/payment-methods', label: 'Moyens de paiement' });
    navItems.push({ to: '/admin/audit-logs', label: "Journal d'audit" });
  }

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="brand">App Paie</div>
        <nav>
          {navItems.map((item) => (
            <Link key={item.to} to={item.to} className={location.pathname === item.to ? 'active' : ''}>
              {item.label}
            </Link>
          ))}
        </nav>
      </aside>
      <div className="main">
        <header className="topbar">
          <span>{(user?.name as string) ?? ''}</span>
          <span className="role-badge">{role}</span>
          <button onClick={() => logout()}>Déconnexion</button>
        </header>
        <main className="content">{children}</main>
      </div>
    </div>
  );
}