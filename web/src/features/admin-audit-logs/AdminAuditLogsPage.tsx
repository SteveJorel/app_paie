import { Fragment, useEffect, useRef, useState, type FormEvent } from 'react';
import { adminAuditLogsRepository, type AuditLogRow, type AuditLogFilters } from './adminAuditLogsRepository';
import { ApiException } from '../../core/apiException';
import { formatDateTime } from '../../core/format';

function shortType(type: string) {
  const parts = type.split('\\');
  return parts[parts.length - 1];
}

function formatChanges(changes: unknown): string {
  // La base renvoie le JSON sous forme de texte : on le relit pour l'afficher indenté.
  let value = changes;

  if (typeof value === 'string') {
    try {
      value = JSON.parse(value);
    } catch {
      return value as string;
    }
  }

  return JSON.stringify(value, null, 2);
}

export default function AdminAuditLogsPage() {
  const [rows, setRows] = useState<AuditLogRow[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [filters, setFilters] = useState<AuditLogFilters>({}); // saisie en cours
  const [appliedFilters, setAppliedFilters] = useState<AuditLogFilters>({}); // filtres réellement appliqués
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [expanded, setExpanded] = useState<number | null>(null);
  const latestRequest = useRef(0);

  async function load(targetPage: number, activeFilters: AuditLogFilters) {
    const requestId = ++latestRequest.current;
    setIsLoading(true);
    setError(null);
    setExpanded(null);
    try {
      const result = await adminAuditLogsRepository.list({ ...activeFilters, page: targetPage });
      if (requestId !== latestRequest.current) return; // réponse périmée : une requête plus récente est partie
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
    load(1, {});
  }, []);

  function handleFilterSubmit(event: FormEvent) {
    event.preventDefault();
    setAppliedFilters(filters);
    load(1, filters);
  }

  function handleReset() {
    setFilters({});
    setAppliedFilters({});
    load(1, {});
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
        <button type="button" className="btn btn-outline" onClick={handleReset}>
          Réinitialiser
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
                    <td>{formatDateTime(row.created_at)}</td>
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
                        <pre className="json-preview">{formatChanges(row.changes)}</pre>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
            </tbody>
          </table>

          <div className="pagination">
            <button className="btn btn-outline" disabled={page <= 1} onClick={() => load(page - 1, appliedFilters)}>
              ← Précédent
            </button>
            <span>
              Page {page} / {lastPage}
            </span>
            <button className="btn btn-outline" disabled={page >= lastPage} onClick={() => load(page + 1, appliedFilters)}>
              Suivant →
            </button>
          </div>
        </>
      )}
    </div>
  );
}