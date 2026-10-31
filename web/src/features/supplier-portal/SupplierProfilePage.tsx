import { useEffect, useState } from 'react';
import { supplierPortalRepository, type SupplierProfile } from './supplierPortalRepository';
import { ApiException } from '../../core/apiException';
import { formatDateTime } from '../../core/format';

const COUNTRY_LABELS: Record<string, string> = {
  CM: 'Cameroun (CM)',
  NG: 'Nigeria (NG)',
};

const STATUS_LABELS: Record<string, string> = {
  pending: 'En attente de validation',
  active: 'Actif',
  suspended: 'Suspendu',
};

export default function SupplierProfilePage() {
  const [profile, setProfile] = useState<SupplierProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    supplierPortalRepository
      .profile()
      .then((result) => {
        if (!cancelled) setProfile(result);
      })
      .catch((e) => {
        if (!cancelled) setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });

    return () => {
      cancelled = true;
    };
  }, []);

  const rows: [string, string][] = profile
    ? [
        ['Raison sociale', profile.business_name],
        ['Pays', COUNTRY_LABELS[profile.country_code] ?? profile.country_code],
        ['Catégorie', profile.category ?? '-'],
        ['Devise de reversement', profile.settlement_currency],
        ['E-mail de contact', profile.contact_email ?? '-'],
        ['Téléphone de contact', profile.contact_phone ?? '-'],
        ['Inscrit le', formatDateTime(profile.created_at)],
      ]
    : [];

  return (
    <div>
      <div className="toolbar">
        <h1>Mon profil</h1>
      </div>

      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        profile && (
          <>
            <p style={{ marginBottom: 16 }}>
              Statut :{' '}
              <span className={`badge badge-${profile.status}`}>{STATUS_LABELS[profile.status] ?? profile.status}</span>
            </p>
            <table>
              <tbody>
                {rows.map(([label, value]) => (
                  <tr key={label}>
                    <th style={{ width: 240, textAlign: 'left' }}>{label}</th>
                    <td>{value}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <p className="hint" style={{ marginTop: 16 }}>
              Pour modifier ces informations, contactez l'administrateur de la plateforme.
            </p>
          </>
        )
      )}
    </div>
  );
}