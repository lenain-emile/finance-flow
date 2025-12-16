import { useEffect } from 'react'
import { Navigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'

/**
 * Composant de protection des routes
 * Redirige vers la page de connexion si l'utilisateur n'est pas authentifié
 */
export function ProtectedRoute({ children, redirectTo = '/login' }) {
  const { isAuthenticated, isLoading, getCurrentUser } = useAuth()

  useEffect(() => {
    // Vérifier l'authentification au chargement du composant
    if (!isLoading && !isAuthenticated) {
      const token = localStorage.getItem('auth_token')
      if (token) {
        // Tenter de récupérer les données utilisateur
        getCurrentUser().catch(() => {
          // Token invalide, nettoyer le stockage
          localStorage.removeItem('auth_token')
          localStorage.removeItem('refresh_token')
          localStorage.removeItem('user_data')
        })
      }
    }
  }, [isLoading, isAuthenticated, getCurrentUser])

  // Affichage du loader pendant la vérification
  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-100">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
          <p className="text-gray-600">Vérification de l'authentification...</p>
        </div>
      </div>
    )
  }

  // Si pas authentifié, rediriger vers la page de connexion
  if (!isAuthenticated) {
    return <Navigate to={redirectTo} replace />
  }

  // Utilisateur authentifié, afficher le contenu protégé
  return children
}

export default ProtectedRoute