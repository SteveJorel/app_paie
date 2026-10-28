import { apiClient } from '../../core/apiClient';

export interface Supplier {
  id: number;
  business_name?: string;
  name?: string;
  country_code?: string;
  category?: string;
  settlement_currency?: string;
  status: string;
  contact_email?: string;
  contact_phone?: string;
}

function extractList(response: unknown): Supplier[] {
  if (response && typeof response === 'object' && 'data' in response) {
    const data = (response as { data: unknown }).data;
    if (Array.isArray(data)) return data as Supplier[];
  }
  return [];
}

export const adminSuppliersRepository = {
  async list(status?: string): Promise<Supplier[]> {
    const query = status ? `?status=${status}` : '';
    const response = await apiClient.get(`/admin/suppliers${query}`);
    return extractList(response);
  },

  async create(payload: {
    business_name: string;
    country_code: string;
    category: string;
    settlement_currency: string;
    contact_email?: string;
    contact_phone?: string;
  }) {
    return apiClient.post('/admin/suppliers', payload);
  },

  async approve(id: number) {
    return apiClient.post(`/admin/suppliers/${id}/approve`, {});
  },

  async suspend(id: number) {
    return apiClient.post(`/admin/suppliers/${id}/suspend`, {});
  },
};