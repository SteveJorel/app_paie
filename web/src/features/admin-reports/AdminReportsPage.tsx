import { useEffect, useState } from 'react';
import { adminReportsRepository, type SettlementsReport } from './adminReportsRepository';
import { ApiException } from '../../core/apiException';

function formatMinor(amountMinor: number, currency: string) {
  const factor = currency === 'NGN' ? 100 : 1;
  return `${(amountMinor / factor).toLocaleString('fr-FR')} ${currency}`;
}

export default function AdminReportsPage() {
  const [report, setReport] = useState<SettlementsReport | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    adminReportsRepository
      .settlements()
      .then(setReport)
      .catch((e) => setError(e instanceof ApiException ? e.message : 'Connexion impossible.'))
      .finally(() => setIsLoading(false));
  }, []);

  if (isLoading) return <p>Chargement...</p>;
  if (error) return <p className="auth-error">{error}</p>;
  if (!report) return null;

  const currencies = Object.keys(report.by_currency);

  return (
    <div>
      <div className="toolbar">
        <h1>Rapport de rapprochement</h1>
      </div>

      <div className="stats-grid">
        <div className="stat-card">
          <span className="stat-label">Paiements échoués</span>
          <span className="stat-value">{report.payments_failed_count}</span>
        </div>
        <div className="stat-card">
          <span className="stat-label">Paiements expirés</span>
          <span className="stat-value">{report.payments_expired_count}</span>
        </div>
        <div className="stat-card stat-warning">
          <span className="stat-label">Anomalies</span>
          <span className="stat-value">{report.anomalies_count}</span>
        </div>
        <div className="stat-card stat-warning">
          <span className="stat-label">Écarts de montant</span>
          <span className="stat-value">{report.amount_mismatches_count}</span>
        </div>
      </div>

      {currencies.length === 0 && <p>Aucune donnée pour le moment.</p>}

      {currencies.map((currency) => {
        const row = report.by_currency[currency];
        return (
          <div key={currency} style={{ marginTop: 24 }}>
            <h2>{currency}</h2>
            <table>
              <tbody>
                <tr>
                  <th>Paiements confirmés</th>
                  <td>{row.payments_paid_count}</td>
                </tr>
                <tr>
                  <th>Volume payé</th>
                  <td>{formatMinor(row.volume_paid_minor, currency)}</td>
                </tr>
                <tr>
                  <th>Commissions générées</th>
                  <td>{formatMinor(row.commissions_minor, currency)}</td>
                </tr>
                <tr>
                  <th>Reversements en attente</th>
                  <td>{formatMinor(row.settlements_pending_minor, currency)}</td>
                </tr>
                <tr>
                  <th>Reversements confirmés</th>
                  <td>{formatMinor(row.settlements_settled_minor, currency)}</td>
                </tr>
              </tbody>
            </table>
          </div>
        );
      })}
    </div>
  );
}