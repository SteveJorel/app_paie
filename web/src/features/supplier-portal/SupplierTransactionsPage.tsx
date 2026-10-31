import { useEffect, useRef, useState } from 'react';
import { supplierPortalRepository, type SupplierTransactionRow } from './supplierPortalRepository';
import { ApiException } from '../../core/apiException';
import { formatMinor, formatDateTime, STATUS_LABELS, statusBadgeClass } from '../../core/format';
import { downloadCsv, minorToDecimal } from '../../core/csv';

const ALLOCATION_LABELS: Record<string, string> = {
  pending: 'En attente',
  settled: 'Reversé',
  refunded: 'Remboursé',
};

function allocationBadgeClass(status: string): string {
  if (status === 'settled') return 'badge-active';
  if (status === 'refunded') return 'badge-failed';
  return 'badge-pending';
}

export default function SupplierTransactionsPage() {
  const [rows, setRows] = useState<SupplierTransactionRow[]>([]);
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
      const result = await supplierPortalRepository.transactions(targetPage);
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

  function handleExport() {
    downloadCsv(
      `transactions-page-${page}.csv`,
      ['Référence paiement', 'Commande', 'Description', 'Devise', 'Brut', 'Frais', 'Net', 'Statut paiement', 'Statut reversement', 'Confirmé le', 'Créé le'],
      rows.map((r) => [
        r.payment_reference,
        r.order_reference,
        r.description ?? '',
        r.currency,
        minorToDecimal(r.gross_amount_minor, r.currency),
        minorToDecimal(r.fees_minor, r.currency),
        minorToDecimal(r.net_amount_minor, r.currency),
        STATUS_LABELS[r.payment_status] ?? r.payment_status,
        ALLOCATION_LABELS[r.allocation_status] ?? r.allocation_status,
        formatDateTime(r.confirmed_at),
        formatDateTime(r.created_at),
      ]),
    );
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Mes transactions</h1>
        <button className="btn btn-outline" disabled={isLoading || rows.length === 0} onClick={handleExport}>
          Exporter cette page (CSV)
        </button>
      </div>

      {error && <p className="auth-error">{error}</p>}

      {isLoading ? (
        <p>Chargement...</p>
      ) : (
        <>
          <table>
            <thead>
              <tr>
                <th>Référence</th>
                <th>Commande</th>
                <th>Description</th>
                <th>Brut</th>
                <th>Frais</th>
                <th>Net</th>
                <th>Paiement</th>
                <th>Reversement</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={9}>Aucune transaction.</td>
                </tr>
              )}
              {rows.map((row, index) => (
                <tr key={`${row.payment_reference}-${index}`}>
                  <td>{row.payment_reference}</td>
                  <td>{row.order_reference}</td>
                  <td>{row.description ?? '-'}</td>
                  <td>{formatMinor(row.gross_amount_minor, row.currency)}</td>
                  <td>{formatMinor(row.fees_minor, row.currency)}</td>
                  <td>{formatMinor(row.net_amount_minor, row.currency)}</td>
                  <td>
                    <span className={`badge ${statusBadgeClass(row.payment_status)}`}>
                      {STATUS_LABELS[row.payment_status] ?? row.payment_status}
                    </span>
                  </td>
                  <td>
                    <span className={`badge ${allocationBadgeClass(row.allocation_status)}`}>
                      {ALLOCATION_LABELS[row.allocation_status] ?? row.allocation_status}
                    </span>
                  </td>
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