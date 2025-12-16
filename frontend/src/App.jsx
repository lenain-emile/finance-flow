import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from '@/contexts/AuthContext'
import { TransactionProvider } from '@/contexts/TransactionContext'
import { 
  RegisterPage, 
  LoginPage, 
  AddTransactionPage, 
  DashboardPage, 
  CreateAccountPage,
  CreateBudgetPage,
  CreatePlannedTransactionPage
} from '@/components/pages'
import { ProtectedRoute } from '@/components/molecules'

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <TransactionProvider>
          <Routes>
            {/* Routes publiques */}
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            
            {/* Routes protégées */}
            <Route path="/dashboard" element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            } />
            <Route path="/add-transaction" element={
              <ProtectedRoute>
                <AddTransactionPage />
              </ProtectedRoute>
            } />
            <Route path="/accounts/create" element={
              <ProtectedRoute>
                <CreateAccountPage />
              </ProtectedRoute>
            } />
            <Route path="/budgets/create" element={
              <ProtectedRoute>
                <CreateBudgetPage />
              </ProtectedRoute>
            } />
            <Route path="/planned-transactions/create" element={
              <ProtectedRoute>
                <CreatePlannedTransactionPage />
              </ProtectedRoute>
            } />
            
            {/* Redirection par défaut */}
            <Route path="/" element={<Navigate to="/dashboard" replace />} />
            
            {/* Route 404 - Redirection vers dashboard */}
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
          </Routes>
        </TransactionProvider>
      </AuthProvider>
    </BrowserRouter>
  )
}

export default App