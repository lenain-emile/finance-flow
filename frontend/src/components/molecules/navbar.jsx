import { useState } from "react"
import { Link, useNavigate } from "react-router-dom"
import { Button } from "@/components/atoms"
import { useAuth } from "@/hooks/useAuth"
import "@/styles/components/navbar.css"

export function Navbar({ currentPage = "home" }) {
  const [isMenuOpen, setIsMenuOpen] = useState(false)
  const { isAuthenticated, user, logout } = useAuth()
  const navigate = useNavigate()

  const toggleMenu = () => {
    setIsMenuOpen(!isMenuOpen)
  }

  const handleLogout = async () => {
    try {
      await logout()
      navigate('/')
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error)
    }
  }

  return (
    <nav className="navbar">
      <div className="navbar__container">
        <div className="navbar__header">
          
          {/* Logo */}
          <div className="navbar__logo-container">
            <Link to="/" className="navbar__logo">
              <div className="navbar__logo-icon">
                <span className="navbar__logo-text">FF</span>
              </div>
              <div className="navbar__brand-container">
                <span className="navbar__brand-text">
                  Finance Flow
                </span>
                <p className="navbar__brand-subtitle">Gérez vos finances</p>
              </div>
            </Link>
          </div>

          {/* Navigation Links - Desktop */}
          <div className="navbar__nav-desktop">
            <div className="navbar__nav-list">
              <Link
                to="/"
                className={currentPage === "home" ? "navbar__nav-link navbar__nav-link--active" : "navbar__nav-link"}
              >
                Accueil
              </Link>
              <Link
                to="/dashboard"
                className={currentPage === "dashboard" ? "navbar__nav-link navbar__nav-link--active" : "navbar__nav-link"}
              >
                Dashboard
              </Link>
              {isAuthenticated && (
                <>
                  <Link
                    to="/add-transaction"
                    className={currentPage === "add-transaction" ? "navbar__nav-link navbar__nav-link--active" : "navbar__nav-link"}
                  >
                    Ajouter Transaction
                  </Link>
                  <Link
                    to="/planned-transactions/create"
                    className={currentPage === "planned-transactions" ? "navbar__nav-link navbar__nav-link--active" : "navbar__nav-link"}
                  >
                    Transaction planifiée
                  </Link>
                </>
              )}
            </div>
          </div>

          {/* Actions - Desktop */}
          <div className="navbar__actions-desktop">
            {isAuthenticated ? (
              <>
                <div className="flex items-center space-x-3">
                  <span className="text-sm text-gray-600">
                    Bonjour, {user?.name || user?.username || 'Utilisateur'}
                  </span>
                  <Button 
                    variant="outline" 
                    size="sm"
                    className="navbar__btn-logout"
                    onClick={handleLogout}
                  >
                    Déconnexion
                  </Button>
                </div>
              </>
            ) : (
              <>
                <Button 
                  variant="outline" 
                  size="sm"
                  className="navbar__btn-login"
                  onClick={() => navigate('/login')}
                >
                  Connexion
                </Button>
                <Button 
                  size="sm"
                  className="navbar__btn-register"
                  onClick={() => navigate('/register')}
                >
                  Inscription
                </Button>
              </>
            )}
          </div>

          {/* Mobile menu button */}
          <div className="navbar__mobile-toggle">
            <button
              onClick={toggleMenu}
              className="navbar__mobile-button"
            >
              <span className="sr-only">Ouvrir le menu</span>
              <svg
                className={`navbar__mobile-icon ${isMenuOpen ? 'navbar__mobile-icon--open' : ''}`}
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                {isMenuOpen ? (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                ) : (
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                )}
              </svg>
            </button>
          </div>
        </div>

        {/* Mobile menu */}
        {isMenuOpen && (
          <div className="navbar__mobile-menu">
            <div className="navbar__mobile-container">
              {/* Mobile Navigation Links */}
              <Link
                to="/"
                className={currentPage === "home" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
              >
                🏠 Accueil
              </Link>
              <Link
                to="/dashboard"
                className={currentPage === "dashboard" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
              >
                📊 Dashboard
              </Link>
              <Link
                to="/transactions"
                className={currentPage === "transactions" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
              >
                💳 Transactions
              </Link>
              {isAuthenticated && (
                <>
                  <Link
                    to="/add-transaction"
                    className={currentPage === "add-transaction" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
                  >
                    ➕ Ajouter Transaction
                  </Link>
                  <Link
                    to="/planned-transactions/create"
                    className={currentPage === "planned-transactions" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
                  >
                    🗓️ Transaction planifiée
                  </Link>
                </>
              )}
              <Link
                to="/analytics"
                className={currentPage === "analytics" ? "navbar__mobile-link navbar__mobile-link--active" : "navbar__mobile-link"}
              >
                📈 Analyses
              </Link>
              
              {/* Mobile Actions */}
              <div className="navbar__mobile-actions">
                <div className="navbar__mobile-buttons">
                  {isAuthenticated ? (
                    <>
                      <div className="text-center mb-4">
                        <span className="text-sm text-gray-600">
                          Bonjour, {user?.name || user?.username || 'Utilisateur'}
                        </span>
                      </div>
                      <Button 
                        variant="outline" 
                        className="navbar__mobile-btn-logout w-full"
                        onClick={handleLogout}
                      >
                        Déconnexion
                      </Button>
                    </>
                  ) : (
                    <>
                      <Button 
                        variant="outline" 
                        className="navbar__mobile-btn-login"
                        onClick={() => navigate('/login')}
                      >
                        Connexion
                      </Button>
                      <Button 
                        className="navbar__mobile-btn-register"
                        onClick={() => navigate('/register')}
                      >
                        Inscription
                      </Button>
                    </>
                  )}
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </nav>
  )
}

export default Navbar