import { AuthProvider } from './contexts/AuthContext'
import { RegisterPage } from './components/pages/register-page'
import { LoginPage } from './components/pages/login-page'

function App() {
  // Pour la démonstration, nous allons afficher la page d'inscription
  // Dans une vraie application, vous utiliseriez React Router
  const currentPath = window.location.pathname
  
  return (
    <AuthProvider>
      <div className="App">
        {/* Routage simple pour la démonstration */}
        {currentPath === '/register' && <RegisterPage />}
        {currentPath === '/login' && <LoginPage />}
        {currentPath === '/' && (
          <div className="min-h-screen flex items-center justify-center bg-gray-100">
            <div className="text-center">
              <h1 className="text-4xl font-bold text-gray-800 mb-8">Finance Flow</h1>
              <div className="space-x-4">
                <a 
                  href="/login" 
                  className="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                  Se connecter
                </a>
                <a 
                  href="/register" 
                  className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                >
                  S'inscrire
                </a>
              </div>
            </div>
          </div>
        )}
      </div>
    </AuthProvider>
  )
}

export default App