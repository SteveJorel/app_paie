import { apiClient } from '../../core/apiClient';
import { extractPaginated, type Paginated } from '../../core/pagination';

export interface AuditLogRow {
  id: number;
  actor_id: number | null;
  actor_role: string | null;
  action: string;
  auditable_type: string;
  auditable_id: number | null;
  changes: Record<string, unknown> | null;
  created_at: string;
}

export interface AuditLogFilters {
  action?: string;
  from?: string;
  to?: string;
  page?: number;
}

function buildQuery(filters: AuditLogFilters): string {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== '') params.set(key, String(value));
  });
  return params.toString();
}

export const adminAuditLogsRepository = {
  async list(filters: AuditLogFilters): Promise<Paginated<AuditLogRow>> {
    const query = buildQuery(filters);
    const response = await apiClient.get(`/admin/audit-logs${query ? `?${query}` : ''}`);
    return extractPaginated<AuditLogRow>(response);
  },
};