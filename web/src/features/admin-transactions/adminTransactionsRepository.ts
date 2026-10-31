import { apiClient } from '../../core/apiClient';
import { extractPaginated, type Paginated } from '../../core/pagination';

export interface TransactionRow {
  id: number;
  payment_reference: string;
  order_reference: string;
  description: string;
  status: string;
  amount_minor: number;
  currency: string;
  payment_method: string;
  confirmed_at: string | null;
  created_at: string;
  supplier_id: number;
  supplier_name: string;
  country_code: string;
}

export interface TransactionFilters {
  country_code?: string;
  currency?: string;
  status?: string;
  reference?: string;
  from?: string;
  to?: string;
  page?: number;
}

export interface RefundResult {
  reference: string;
  amount_minor: number;
  currency: string;
}

function buildQuery(filters: TransactionFilters): string {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value !== undefined && value !== '') params.set(key, String(value));
  });
  return params.toString();
}

export const adminTransactionsRepository = {
  async list(filters: TransactionFilters): Promise<Paginated<TransactionRow>> {
    const query = buildQuery(filters);
    const response = await apiClient.get(`/admin/transactions${query ? `?${query}` : ''}`);
    return extractPaginated<TransactionRow>(response);
  },

  // Remboursement total : le serveur rembourse le montant du paiement.
  async refund(paymentId: number, reason: string): Promise<RefundResult> {
    const response = await apiClient.post('/admin/refunds', { payment_id: paymentId, reason });
    return response.data as RefundResult;
  },

  async openDispute(paymentId: number, reason: string): Promise<void> {
    await apiClient.post(`/admin/payments/${paymentId}/dispute`, { reason });
  },

  async resolveDispute(paymentId: number, resolution: 'rejected' | 'upheld', note: string): Promise<void> {
    await apiClient.post(`/admin/payments/${paymentId}/resolve-dispute`, { resolution, note: note || null });
  },
};