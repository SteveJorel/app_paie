import { useEffect, useRef, useState } from 'react';
import { supplierPortalRepository, type SettlementRow } from './supplierPortalRepository';
import { ApiException } from '../../core/apiException';
import { formatMinor, formatDateTime } from '../../core/format';
import { downloadCsv, minorToDecimal } from '../../core/csv';

const SETTLEMENT_LABELS: Record<string, string> = {
  pending: 'En attente',
  settled: 'Reversé',
};

function settlementBadgeClass(status: string): string {
  return status === 'settled' ? 'badge-active' : 'badge-pending';
}

export default function SupplierSettlementsPage() {
  const [rows, setRows] = useState<SettlementRow[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const latestRequest = useRef(0);

  async function load(targetPage: number) {
    const requestId = ++latestRequest.current;
    setIsLoading(true);
    setError(null);
    try {
      const result = await supplierPortalRepository.settlements(targetPage);
      if (requestId !== latestRequest.current) return;
      setRows(result.data);
      setPage(result.current_page);
      setLastPage(result.last_page);
    } catch (e) {
      if (requestId !== latestRequest.current) return;
      setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
    } finally {
      if (requestId === latestRequest.current) setIsLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    load(1);
  }, []);

  // Totaux de la page affichée, par devise (l'API ne fournit pas de total global).
  const totals = new Map<string, { pending: number; settled: number }>();
  for (const row of rows) {
    const entry = totals.get(row.currency) ?? { pending: 0, settled: 0 };
    if (row.status === 'settled') entry.settled += row.amount_minor;
    else if (row.status === 'pending') entry.pending += row.amount_minor;
    totals.set(row.currency, entry);
  }

  function handleExport() {
    downloadCsv(
      `reversements-page-${page}.csv`,
      ['Référence', 'Devise', 'Montant', 'Statut', 'Reversé le', 'Créé le'],
      rows.map((r) => [
        r.reference,
        r.currency,
        minorToDecimal(r.amount_minor, r.currency),
        SETTLEMENT_LABELS[r.status] ?? r.status,
        formatDateTime(r.settled_at),
        formatDateTime(r.created_at),
      ]),
    );
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Mes reversements</h1>
        <button className="btn btn-outline" disabled={isLoading || rows.length === 0} onClick={handleExport}>
          Exporter cette page (CSV)
        </button>
      </div>

      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        <>
          {totals.size > 0 && (
            <>
              <p className="hint" style={{ marginBottom: 8 }}>
                Totaux de la page affichée uniquement.
              </p>
              <div className="stats-grid">
                {[...totals.entries()].map(([currency, t]) => (
                  <div key={currency} style={{ display: 'contents' }}>
                    <div className="stat-card">
                      <div className="stat-label">En attente ({currency})</div>
                      <div className="stat-value">{formatMinor(t.pending, currency)}</div>
                    </div>
                    <div className="stat-card">
                      <div className="stat-label">Reversé ({currency})</div>
                      <div className="stat-value">{formatMinor(t.settled, currency)}</div>
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Reversé le</th>
                <th>Créé le</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={5}>Aucun reversement.</td>
                </tr>
              )}
              {rows.map((row) => (
                <tr key={row.id}>
                  <td>{row.reference}</td>
                  <td>{formatMinor(row.amount_minor, row.currency)}</td>
                  <td>
                    <span className={`badge ${settlementBadgeClass(row.status)}`}>
                      {SETTLEMENT_LABELS[row.status] ?? row.status}
                    </span>
                  </td>
                  <td>{formatDateTime(row.settled_at)}</td>
                  <td>{formatDateTime(row.created_at)}</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="pagination">
            <button className="btn btn-outline" disabled={page <= 1} onClick={() => load(page - 1)}>
              ← Précédent
            </button>
            <span>
              Page {page} / {lastPage}
            </span>
            <button className="btn btn-outline" disabled={page >= lastPage} onClick={() => load(page + 1)}>
              Suivant →
            </button>
          </div>
        </>
      )}
    </div>
  );
}