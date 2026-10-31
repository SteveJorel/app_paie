import { useEffect, useState } from 'react';
import { adminPaymentMethodsRepository, type CountryPaymentMethod } from './adminPaymentMethodsRepository';
import { ApiException } from '../../core/apiException';

const METHOD_LABELS: Record<string, string> = {
  mobile_money: 'Mobile Money',
  card: 'Carte bancaire',
  bank_transfer: 'Virement bancaire',
};

const STATUS_LABELS: Record<string, string> = {
  pending: 'À valider',
  active: 'Actif',
  inactive: 'Inactif',
};

const COUNTRY_LABELS: Record<string, string> = {
  CM: 'Cameroun (CM)',
  NG: 'Nigeria (NG)',
};

export default function AdminPaymentMethodsPage() {
  const [country, setCountry] = useState('');
  const [rows, setRows] = useState<CountryPaymentMethod[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [banner, setBanner] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

  async function load(target: string) {
    setIsLoading(true);
    setError(null);
    try {
      setRows(await adminPaymentMethodsRepository.list(target || undefined));
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
    } finally {
      setIsLoading(false);
    }
  }

    useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    load(country);
  }, [country]);

  async function handleChange(row: CountryPaymentMethod, status: string) {
    if (status === row.status) return;

    const label = METHOD_LABELS[row.payment_method_code] ?? row.payment_method_code;

    if (status === 'inactive') {
      const ok = window.confirm(
        `Désactiver « ${label} » pour ${COUNTRY_LABELS[row.country_code] ?? row.country_code} ? ` +
          'Les clients de ce pays ne pourront plus payer avec ce moyen.',
      );
      if (!ok) return;
    }

    setBusyId(row.id);
    setError(null);
    setBanner(null);

    try {
      const updated = await adminPaymentMethodsRepository.setStatus(row.country_code, row.payment_method_code, status);
      const newStatus = updated?.status ?? status;
      setRows((current) => current.map((r) => (r.id === row.id ? { ...r, status: newStatus } : r)));
      setBanner(
        `${label} (${row.country_code}) : ${STATUS_LABELS[newStatus] ?? newStatus}. Changement enregistré dans le journal d'audit.`,
      );
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Modification impossible.');
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Moyens de paiement par pays</h1>
      </div>

      <p className="hint" style={{ marginBottom: 16 }}>
        Seuls les moyens « Actif » sont proposés aux clients dans l'application.
      </p>

      <div className="filters-bar">
        <select value={country} onChange={(e) => setCountry(e.target.value)}>
          <option value="">Tous les pays</option>
          <option value="CM">Cameroun</option>
          <option value="NG">Nigeria</option>
        </select>
      </div>

      {banner && <p className="banner-success">{banner}</p>}
      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>Pays</th>
              <th>Moyen de paiement</th>
              <th>Disponibilité</th>
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && (
              <tr>
                <td colSpan={3}>Aucune configuration.</td>
              </tr>
            )}
            {rows.map((row) => (
              <tr key={row.id}>
                <td>{COUNTRY_LABELS[row.country_code] ?? row.country_code}</td>
                <td>{METHOD_LABELS[row.payment_method_code] ?? row.payment_method_code}</td>
                <td>
                  <select
                    value={row.status}
                    disabled={busyId === row.id}
                    onChange={(e) => handleChange(row, e.target.value)}
                  >
                    {Object.entries(STATUS_LABELS).map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}