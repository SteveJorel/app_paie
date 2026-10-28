import { apiClient } from '../../core/apiClient';

export interface CommissionRule {
  id: number;
  supplier_id: number | null;
  currency: string;
  type: 'percentage' | 'fixed' | 'combined';
  percentage_bps: number | null;
  fixed_minor: number | null;
  valid_from: string;
  valid_until: string | null;
  version: number;
  status: string;
}

function extractList(response: unknown): CommissionRule[] {
  if (response && typeof response === 'object' && 'data' in response) {
    const data = (response as { data: unknown }).data;
    if (Array.isArray(data)) return data as CommissionRule[];
  }
  return [];
}

export const adminCommissionRulesRepository = {
  async list(): Promise<CommissionRule[]> {
    return extractList(await apiClient.get('/admin/commission-rules'));
  },

  async create(payload: {
    supplier_id?: number;
    currency: string;
    type: string;
    percentage_bps?: number;
    fixed_minor?: number;
    valid_from: string;
  }) {
    return apiClient.post('/admin/commission-rules', payload);
  },

  async retire(id: number) {
    return apiClient.post(`/admin/commission-rules/${id}/retire`, {});
  },
};