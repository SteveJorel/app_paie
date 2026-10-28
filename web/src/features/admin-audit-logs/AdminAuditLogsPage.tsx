import { Fragment, useEffect, useState, type FormEvent } from 'react';
import { adminAuditLogsRepository, type AuditLogRow, type AuditLogFilters } from './adminAuditLogsRepository';
import { ApiException } from '../../core/apiException';

function shortType(type: string) {
  const parts = type.split('\\');
  return parts[parts.length - 1];
}

export default function AdminAuditLogsPage() {
  const [rows, setRows] = useState<AuditLogRow[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [filters, setFilters] = useState<AuditLogFilters>({});
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expanded, setExpanded] = useState<number | null>(null);

  async function load(targetPage = page, activeFilters = filters) {
    setIsLoading(true);
    setError(null);
    try {
      const result = await adminAuditLogsRepository.list({ ...activeFilters, page: targetPage });
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
        <h1>Journal d'audit</h1>
      </div>

      <form className="filters-bar" onSubmit={handleFilterSubmit}>
        <input
          placeholder="Action (ex: supplier.active)"
          value={filters.action ?? ''}
          onChange={(e) => setFilters((f) => ({ ...f, action: e.target.value }))}
        />
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
                <th>Date</th>
                <th>Acteur</th>
                <th>Action</th>
                <th>Cible</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td colSpan={5}>Aucune entrée.</td>
                </tr>
              )}
              {rows.map((row) => (
                <Fragment key={row.id}>
                  <tr>
                    <td>{new Date(row.created_at).toLocaleString('fr-FR')}</td>
                    <td>{row.actor_id ? `#${row.actor_id} (${row.actor_role ?? '-'})` : 'Système'}</td>
                    <td>{row.action}</td>
                    <td>
                      {shortType(row.auditable_type)}
                      {row.auditable_id ? ` #${row.auditable_id}` : ''}
                    </td>
                    <td>
                      {row.changes && (
                        <button className="btn btn-outline" onClick={() => setExpanded(expanded === row.id ? null : row.id)}>
                          {expanded === row.id ? 'Masquer' : 'Détails'}
                        </button>
                      )}
                    </td>
                  </tr>
                  {expanded === row.id && row.changes && (
                    <tr>
                      <td colSpan={5}>
                        <pre className="json-preview">{JSON.stringify(row.changes, null, 2)}</pre>
                      </td>
                    </tr>
                  )}
                </Fragment>
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