import { apiClient } from '../../core/apiClient';

export interface CountryPaymentMethod {
  id: number;
  country_code: string;
  payment_method_code: string;
  status: string; // pending | active | inactive
}

export const adminPaymentMethodsRepository = {
  async list(country?: string): Promise<CountryPaymentMethod[]> {
    const query = country ? `?country=${encodeURIComponent(country)}` : '';
    const response = await apiClient.get(`/admin/payment-methods${query}`);
    return Array.isArray(response?.data) ? (response.data as CountryPaymentMethod[]) : [];
  },

  async setStatus(country: string, method: string, status: string): Promise<CountryPaymentMethod> {
    const response = await apiClient.patch(
      `/admin/payment-methods/${encodeURIComponent(country)}/${encodeURIComponent(method)}`,
      { status },
    );
    return response.data as CountryPaymentMethod;
  },
};