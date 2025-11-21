import { AuthProvider } from './contexts/AuthContext'
import { TransactionProvider } from './contexts/TransactionContext'
import { RegisterPage } from './components/pages/register-page'
import { LoginPage } from './components/pages/login-page'
import { AddTransactionPage } from './components/pages/add-transaction-page'

function App() {
  // Pour la démonstration, nous allons afficher la page d'inscription
  // Dans une vraie application, vous utiliseriez React Router
  const currentPath = window.location.pathname
  
  return (
    <AuthProvider>
      <TransactionProvider>
        <div className="App">
          {/* Routage simple pour la démonstration */}
          {currentPath === '/register' && <RegisterPage />}
          {currentPath === '/login' && <LoginPage />}
          {currentPath === '/add-transaction' && <AddTransactionPage />}
          {currentPath === '/' && (
            <div className="min-h-screen flex items-center justify-center bg-gray-100">
              <div className="text-center">
                <h1 className="text-4xl font-bold text-gray-800 mb-8">Finance Flow</h1>
                <div className="space-x-4 space-y-2">
                  <div>
                    <a 
                      href="/login" 
                      className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mx-2"
                    >
                      Se connecter
                    </a>
                    <a 
                      href="/register" 
                      className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mx-2"
                    >
                      S'inscrire
                    </a>
                  </div>
                  <div>
                    <a 
                      href="/add-transaction" 
                      className="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded mx-2"
                    >
                      Ajouter Transaction
                    </a>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </TransactionProvider>
    </AuthProvider>
  )
}

export default App