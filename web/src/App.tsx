import { Routes, Route, Navigate } from 'react-router-dom';
import LoginPage from './features/auth/LoginPage';
import ProtectedRoute from './features/auth/ProtectedRoute';
import Layout from './components/Layout';
import DashboardPage from './pages/DashboardPage';
import AdminSuppliersPage from './features/admin-suppliers/AdminSuppliersPage';
import AdminCommissionRulesPage from './features/admin-commission-rules/AdminCommissionRulesPage';
import AdminReportsPage from './features/admin-reports/AdminReportsPage';

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
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}