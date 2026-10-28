import { apiClient } from '../../core/apiClient';

export interface CurrencyBreakdown {
  payments_paid_count: number;
  volume_paid_minor: number;
  commissions_minor: number;
  settlements_pending_minor: number;
  settlements_settled_minor: number;
}

export interface SettlementsReport {
  by_currency: Record<string, CurrencyBreakdown>;
  payments_failed_count: number;
  payments_expired_count: number;
  anomalies_count: number;
  amount_mismatches_count: number;
}

export const adminReportsRepository = {
  async settlements(): Promise<SettlementsReport> {
    const response = await apiClient.get('/admin/reports/settlements');
    if (response && typeof response === 'object' && 'data' in response) {
      return (response as { data: SettlementsReport }).data;
    }
    return response as SettlementsReport;
  },
};