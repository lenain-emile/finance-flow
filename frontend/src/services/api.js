import axios from 'axios'

// Configuration de base d'Axios
const api = axios.create({
  baseURL: 'http://localhost/finance-flow/backend/public',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  }
})

// Intercepteur pour ajouter le token d'authentification automatiquement
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Variable pour éviter les redirections multiples
let isRedirecting = false

// Intercepteur pour gérer les réponses et les erreurs
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error) => {
    const originalRequest = error.config

    // Gestion du token expiré (401)
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true

      try {
        const refreshToken = localStorage.getItem('refresh_token')
        if (refreshToken) {
          const response = await api.post('/api/users/refresh-token', {
            refresh_token: refreshToken
          })

          if (response.data?.data?.access_token) {
            const { access_token } = response.data.data
            localStorage.setItem('auth_token', access_token)

            // Réessayer la requête originale avec le nouveau token
            originalRequest.headers.Authorization = `Bearer ${access_token}`
            return api(originalRequest)
          }
        }
      } catch (refreshError) {
        // Échec du rafraîchissement
      }
      
      // Pas de refresh token ou échec, rediriger vers login
      if (!isRedirecting) {
        isRedirecting = true
        localStorage.removeItem('auth_token')
        localStorage.removeItem('refresh_token')
        localStorage.removeItem('user_data')
        if (window.location.pathname !== '/login') {
          window.location.href = '/login'
        }
      }
    }

    return Promise.reject(error)
  }
)

export default api