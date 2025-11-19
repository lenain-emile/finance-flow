import api from './api'

/**
 * Service d'authentification
 * Gère l'inscription, la connexion, la déconnexion et les tokens
 */
class AuthService {
  /**
   * Inscrire un nouvel utilisateur
   * @param {Object} userData - Données de l'utilisateur
   * @param {string} userData.username - Nom d'utilisateur
   * @param {string} userData.email - Email
   * @param {string} userData.password - Mot de passe
   * @param {string} [userData.firstName] - Prénom (optionnel)
   * @param {string} [userData.lastName] - Nom (optionnel)
   * @param {string} [userData.phone] - Téléphone (optionnel)
   * @returns {Promise<Object>} Réponse de l'API
   */
  async register(userData) {
    try {
      const response = await api.post('/api/users/register', {
        username: userData.name, // Le frontend utilise 'name', le backend 'username'
        email: userData.email,
        password: userData.password,
        first_name: userData.firstName || null,
        last_name: userData.lastName || null,
        phone: userData.phone || null
      })

      // Stocker les informations utilisateur si l'inscription réussit
      if (response.data.success && response.data.data.user) {
        const userData = response.data.data.user
        localStorage.setItem('user_data', JSON.stringify(userData))
        
        // Si un token d'accès est fourni (connexion automatique après inscription)
        if (userData.access_token) {
          this.setTokens(userData.access_token, userData.refresh_token)
        }
      }

      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Connecter un utilisateur
   * @param {Object} credentials - Identifiants de connexion
   * @param {string} credentials.email - Email
   * @param {string} credentials.password - Mot de passe
   * @returns {Promise<Object>} Réponse de l'API avec tokens et données utilisateur
   */
  async login(credentials) {
    try {
      const response = await api.post('/api/users/login', {
        email: credentials.email,
        password: credentials.password
      })

      if (response.data.success && response.data.data) {
        const userData = response.data.data
        
        // Stocker les tokens
        this.setTokens(userData.access_token, userData.refresh_token)
        
        // Stocker les données utilisateur (sans les tokens)
        const userInfo = { ...userData }
        delete userInfo.access_token
        delete userInfo.refresh_token
        delete userInfo.token_type
        delete userInfo.expires_in
        
        localStorage.setItem('user_data', JSON.stringify(userInfo))
      }

      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Déconnecter l'utilisateur
   * @returns {Promise<Object>} Réponse de l'API
   */
  async logout() {
    try {
      // Appeler l'API de déconnexion si un token existe
      const token = this.getToken()
      if (token) {
        await api.post('/api/users/logout')
      }
    } catch (error) {
      // Continuer même si l'API échoue
      console.warn('Erreur lors de la déconnexion API:', error)
    } finally {
      // Nettoyer le stockage local dans tous les cas
      this.clearAuth()
    }
  }

  /**
   * Rafraîchir le token d'accès
   * @returns {Promise<Object>} Nouveau token d'accès
   */
  async refreshToken() {
    const refreshToken = this.getRefreshToken()
    if (!refreshToken) {
      throw new Error('Aucun token de rafraîchissement disponible')
    }

    try {
      const response = await api.post('/api/users/refresh-token', {
        refresh_token: refreshToken
      })

      if (response.data.success && response.data.data.access_token) {
        this.setTokens(response.data.data.access_token, refreshToken)
        return response.data
      }

      throw new Error('Erreur lors du rafraîchissement du token')
    } catch (error) {
      this.clearAuth()
      throw this.handleError(error)
    }
  }

  /**
   * Obtenir le profil de l'utilisateur connecté
   * @returns {Promise<Object>} Données du profil utilisateur
   */
  async getCurrentUser() {
    try {
      const response = await api.get('/api/users/me')
      
      if (response.data.success && response.data.data) {
        // Mettre à jour les données utilisateur en local
        localStorage.setItem('user_data', JSON.stringify(response.data.data))
        return response.data
      }

      throw new Error('Erreur lors de la récupération du profil')
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Vérifier si un nom d'utilisateur est disponible
   * @param {string} username - Nom d'utilisateur à vérifier
   * @returns {Promise<Object>} Disponibilité du nom d'utilisateur
   */
  async checkUsernameAvailability(username) {
    try {
      const response = await api.get(`/api/users/check-username/${encodeURIComponent(username)}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Vérifier si un email est disponible
   * @param {string} email - Email à vérifier
   * @returns {Promise<Object>} Disponibilité de l'email
   */
  async checkEmailAvailability(email) {
    try {
      const response = await api.get(`/api/users/check-email/${encodeURIComponent(email)}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // === Méthodes utilitaires ===

  /**
   * Stocker les tokens d'authentification
   * @param {string} accessToken - Token d'accès
   * @param {string} refreshToken - Token de rafraîchissement
   */
  setTokens(accessToken, refreshToken) {
    localStorage.setItem('auth_token', accessToken)
    if (refreshToken) {
      localStorage.setItem('refresh_token', refreshToken)
    }
  }

  /**
   * Obtenir le token d'accès
   * @returns {string|null} Token d'accès
   */
  getToken() {
    return localStorage.getItem('auth_token')
  }

  /**
   * Obtenir le token de rafraîchissement
   * @returns {string|null} Token de rafraîchissement
   */
  getRefreshToken() {
    return localStorage.getItem('refresh_token')
  }

  /**
   * Obtenir les données utilisateur depuis le stockage local
   * @returns {Object|null} Données utilisateur
   */
  getUserData() {
    try {
      const userData = localStorage.getItem('user_data')
      return userData ? JSON.parse(userData) : null
    } catch (error) {
      console.error('Erreur lors de la lecture des données utilisateur:', error)
      return null
    }
  }

  /**
   * Vérifier si l'utilisateur est connecté
   * @returns {boolean} État de connexion
   */
  isAuthenticated() {
    return !!(this.getToken() && this.getUserData())
  }

  /**
   * Nettoyer toutes les données d'authentification
   */
  clearAuth() {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
    localStorage.removeItem('user_data')
  }

  /**
   * Gérer les erreurs de l'API
   * @param {Error} error - Erreur axios
   * @returns {Error} Erreur formatée
   */
  handleError(error) {
    console.error('Erreur AuthService:', error)

    if (error.response) {
      // Erreur de réponse API
      const { data, status } = error.response
      const message = data?.message || data?.error || `Erreur ${status}`
      
      const formattedError = new Error(message)
      formattedError.status = status
      formattedError.data = data
      return formattedError
    } else if (error.request) {
      // Erreur de réseau
      return new Error('Erreur de connexion au serveur')
    } else {
      // Autre erreur
      return error
    }
  }
}

// Instance unique du service
const authService = new AuthService()

export default authService