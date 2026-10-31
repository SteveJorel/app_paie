import { apiClient } from '../../core/apiClient';
import { extractPaginated, type Paginated } from '../../core/pagination';

export interface SupplierProfile {
  business_name: string;
  country_code: string;
  category: string | null;
  settlement_currency: string;
  contact_email: string | null;
  contact_phone: string | null;
  status: string; // pending | active | suspended
  created_at: string;
}

export interface SupplierTransactionRow {
  payment_reference: string;
  order_reference: string;
  description: string | null;
  payment_status: string;
  currency: string;
  gross_amount_minor: number;
  fees_minor: number;
  net_amount_minor: number;
  allocation_status: string;
  confirmed_at: string | null;
  created_at: string;
}

export interface SettlementRow {
  id: number;
  allocation_id: number;
  supplier_id: number;
  currency: string;
  amount_minor: number;
  status: string; // pending | settled
  reference: string;
  settled_at: string | null;
  created_at: string;
}

export const supplierPortalRepository = {
  async profile(): Promise<SupplierProfile> {
    const response = await apiClient.get('/supplier/profile');
    return response.data as SupplierProfile;
  },

  async transactions(page: number): Promise<Paginated<SupplierTransactionRow>> {
    const response = await apiClient.get(`/supplier/transactions?page=${page}`);
    return extractPaginated<SupplierTransactionRow>(response);
  },

  async settlements(page: number): Promise<Paginated<SettlementRow>> {
    const response = await apiClient.get(`/supplier/settlements?page=${page}`);
    return extractPaginated<SettlementRow>(response);
  },
};