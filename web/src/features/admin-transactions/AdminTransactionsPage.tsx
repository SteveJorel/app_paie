import { useEffect, useState, type FormEvent } from 'react';
import {
  adminTransactionsRepository,
  type TransactionRow,
  type TransactionFilters,
} from './adminTransactionsRepository';
import { ApiException } from '../../core/apiException';

const STATUS_LABELS: Record<string, string> = {
  created: 'Créé',
  pending: 'En attente',
  requires_action: 'Action requise',
  paid: 'Payé',
  failed: 'Échoué',
  expired: 'Expiré',
  refunded: 'Remboursé',
  disputed: 'Contesté',
};

function formatAmount(amountMinor: number, currency: string) {
  const factor = currency === 'NGN' ? 100 : 1;
  return `${(amountMinor / factor).toLocaleString('fr-FR')} ${currency}`;
}

function badgeClass(status: string) {
  if (status === 'paid') return 'badge-active';
  if (status === 'failed' || status === 'expired') return 'badge-failed';
  return 'badge-pending';
}

export default function AdminTransactionsPage() {
  const [rows, setRows] = useState<TransactionRow[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [filters, setFilters] = useState<TransactionFilters>({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  async function load(targetPage = page, activeFilters = filters) {
    setIsLoading(true);
    setError(null);
    try {
      const result = await adminTransactionsRepository.list({ ...activeFilters, page: targetPage });
      setRows(result.data);
      setPage(result.current_page);
      setLastPage(result.last_page);
    } catch (e) {
      setError(e instanceof ApiException ? e.message : 'Connexion impossible.');
    } finally {
      setIsLoading(false);
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect
    load(1, {});
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  function handleFilterSubmit(event: FormEvent) {
    event.preventDefault();
    load(1, filters);
  }

  return (
    <div>
      <div className="toolbar">
        <h1>Transactions</h1>
      </div>

      <form className="filters-bar" onSubmit={handleFilterSubmit}>
        <input
          placeholder="Référence"
          value={filters.reference ?? ''}
          onChange={(e) => setFilters((f) => ({ ...f, reference: e.target.value }))}
        />
        <select
          value={filters.country_code ?? ''}
          onChange={(e) => setFilters((f) => ({ ...f, country_code: e.target.value }))}
        >
          <option value="">Tous les pays</option>
          <option value="CM">Cameroun</option>
          <option value="NG">Nigeria</option>
        </select>
        <select value={filters.currency ?? ''} onChange={(e) => setFilters((f) => ({ ...f, currency: e.target.value }))}>
          <option value="">Toutes les devises</option>
          <option value="XAF">XAF</option>
          <option value="NGN">NGN</option>
        </select>
        <select value={filters.status ?? ''} onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))}>
          <option value="">Tous les statuts</option>
          {Object.entries(STATUS_LABELS).map(([value, label]) => (
            <option key={value} value={value}>
              {label}
            </option>
          ))}
        </select>
        <input type="date" value={filters.from ?? ''} onChange={(e) => setFilters((f) => ({ ...f, from: e.target.value }))} />
        <input type="date" value={filters.to ?? ''} onChange={(e) => setFilters((f) => ({ ...f, to: e.target.value }))} />
        <button type="submit" className="btn btn-primary">
          Filtrer
        </button>
      </form>

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
                <th>Fournisseur</th>
                <th>Pays</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={7}>Aucune transaction.</td>
                </tr>
              )}
              {rows.map((row) => (
                <tr key={row.id}>
                  <td>{row.payment_reference}</td>
                  <td>{row.order_reference}</td>
                  <td>{row.supplier_name}</td>
                  <td>{row.country_code}</td>
                  <td>{formatAmount(row.amount_minor, row.currency)}</td>
                  <td>
                    <span className={`badge ${badgeClass(row.status)}`}>{STATUS_LABELS[row.status] ?? row.status}</span>
                  </td>
                  <td>{new Date(row.created_at).toLocaleString('fr-FR')}</td>
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