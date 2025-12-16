import { useState } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { Navbar, RegisterForm } from "@/components/molecules"
import { useAuth } from "@/hooks/useAuth"
import "@/styles/components/pages.css"

export function RegisterPage() {
  const { register, isLoading, error, clearError } = useAuth()
  const [successMessage, setSuccessMessage] = useState('')
  const navigate = useNavigate()
  
  const handleRegister = async (formData) => {
    try {
      clearError()
      setSuccessMessage('')
      
      const response = await register(formData)
      
      if (response.success) {
        setSuccessMessage(
          'Compte créé avec succès ! Un email de vérification a été envoyé à votre adresse email.'
        )
        // Optionnel : rediriger vers la page de connexion après quelques secondes
        setTimeout(() => {
          navigate('/login')
        }, 3000)
      }
    } catch (err) {
      console.error('Erreur inscription:', err)
      // L'erreur est automatiquement gérée par le contexte
    }
  }

  return (
    <div className="page">
      {/* Navbar */}
      <Navbar currentPage="register" />
      
      {/* Contenu principal */}
      <main className="page__main">
        <div className="page__container">
          
          {/* En-tête */}
          <div className="page__header">
            <div className="page__logo">
              <span className="page__logo-text">FF</span>
            </div>
            <h1 className="page__title">
              Créer votre compte
            </h1>
            <p className="page__subtitle">
              Rejoignez Finance Flow et prenez le contrôle de vos finances
            </p>
          </div>
          
          {/* Messages d'erreur et de succès */}
          {error && (
            <div className="alert alert-danger mb-4 p-3 border border-red-400 bg-red-50 text-red-700 rounded-md">
              <div className="flex items-center">
                <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
                <span>{error}</span>
              </div>
            </div>
          )}
          
          {successMessage && (
            <div className="alert alert-success mb-4 p-3 border border-green-400 bg-green-50 text-green-700 rounded-md">
              <div className="flex items-center">
                <svg className="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                </svg>
                <span>{successMessage}</span>
              </div>
            </div>
          )}
          
          {/* Formulaire dans une carte */}
          <div className="page__card">
            <RegisterForm 
              onSubmit={handleRegister}
              isLoading={isLoading}
              className=""
            />
          </div>
          
          {/* Pied de page */}
          <div className="page__footer">
            <p className="page__footer-text">
              En créant un compte, vous acceptez nos{" "}
              <Link to="/terms" className="page__footer-link">
                conditions d'utilisation
              </Link>{" "}
              et notre{" "}
              <Link to="/privacy" className="page__footer-link">
                politique de confidentialité
              </Link>
            </p>
          </div>
        </div>
      </main>

      {/* Décoration de fond */}
      <div className="page__bg-decoration">
        <div className="page__bg-blob-1"></div>
        <div className="page__bg-blob-2"></div>
      </div>
    </div>
  )
}

export default RegisterPage