import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './features/auth/LoginPage';
import ProtectedRoute from './features/auth/ProtectedRoute';
import Layout from './components/Layout';
import DashboardPage from './pages/DashboardPage';
import AdminSuppliersPage from './features/admin-suppliers/AdminSuppliersPage';
import AdminCommissionRulesPage from './features/admin-commission-rules/AdminCommissionRulesPage';
import AdminReportsPage from './features/admin-reports/AdminReportsPage';
import AdminTransactionsPage from './features/admin-transactions/AdminTransactionsPage';
import AdminPaymentMethodsPage from './features/admin-payment-methods/AdminPaymentMethodsPage';
import AdminAuditLogsPage from './features/admin-audit-logs/AdminAuditLogsPage';
import SupplierProfilePage from './features/supplier-portal/SupplierProfilePage';
import SupplierTransactionsPage from './features/supplier-portal/SupplierTransactionsPage';
import SupplierSettlementsPage from './features/supplier-portal/SupplierSettlementsPage';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout>
              <DashboardPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/supplier/profile"
        element={
          <ProtectedRoute allowedRoles={['supplier']}>
            <Layout>
              <SupplierProfilePage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/supplier/transactions"
        element={
          <ProtectedRoute allowedRoles={['supplier']}>
            <Layout>
              <SupplierTransactionsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/supplier/settlements"
        element={
          <ProtectedRoute allowedRoles={['supplier']}>
            <Layout>
              <SupplierSettlementsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/suppliers"
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout>
              <AdminSuppliersPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/commission-rules"
        element={
          <ProtectedRoute allowedRoles={['admin', 'finance']}>
            <Layout>
              <AdminCommissionRulesPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/reports"
        element={
          <ProtectedRoute allowedRoles={['admin', 'finance']}>
            <Layout>
              <AdminReportsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/transactions"
        element={
          <ProtectedRoute allowedRoles={['admin', 'finance']}>
            <Layout>
              <AdminTransactionsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/payment-methods"
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout>
              <AdminPaymentMethodsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/admin/audit-logs"
        element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Layout>
              <AdminAuditLogsPage />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}