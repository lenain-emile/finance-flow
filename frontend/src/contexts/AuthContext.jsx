import { createContext, useReducer, useEffect } from 'react'
import authService from '@/services/authService'
import { AUTH_ACTIONS } from '@/contexts/authConstants'

// État initial de l'authentification
const initialState = {
  user: null,
  token: null,
  refreshToken: null,
  isAuthenticated: false,
  isLoading: true,
  error: null
}

// Reducer pour gérer l'état d'authentification
function authReducer(state, action) {
  switch (action.type) {
    case AUTH_ACTIONS.LOGIN_START:
    case AUTH_ACTIONS.REGISTER_START:
      return {
        ...state,
        isLoading: true,
        error: null
      }

    case AUTH_ACTIONS.LOGIN_SUCCESS:
    case AUTH_ACTIONS.REGISTER_SUCCESS:
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        refreshToken: action.payload.refreshToken,
        isAuthenticated: true,
        isLoading: false,
        error: null
      }

    case AUTH_ACTIONS.LOGIN_ERROR:
    case AUTH_ACTIONS.REGISTER_ERROR:
      return {
        ...state,
        user: null,
        token: null,
        refreshToken: null,
        isAuthenticated: false,
        isLoading: false,
        error: action.payload
      }

    case AUTH_ACTIONS.LOGOUT:
      return {
        ...state,
        user: null,
        token: null,
        refreshToken: null,
        isAuthenticated: false,
        isLoading: false,
        error: null
      }

    case AUTH_ACTIONS.LOAD_USER:
      return {
        ...state,
        user: action.payload.user,
        token: action.payload.token,
        refreshToken: action.payload.refreshToken,
        isAuthenticated: action.payload.isAuthenticated,
        isLoading: false,
        error: null
      }

    case AUTH_ACTIONS.CLEAR_ERROR:
      return {
        ...state,
        error: null
      }

    case AUTH_ACTIONS.SET_LOADING:
      return {
        ...state,
        isLoading: action.payload
      }

    default:
      return state
  }
}

// Création du contexte
const AuthContext = createContext()

// Provider du contexte d'authentification
export function AuthProvider({ children }) {
  const [state, dispatch] = useReducer(authReducer, initialState)

  // Charger l'utilisateur depuis le stockage local au démarrage
  useEffect(() => {
    const loadUserFromStorage = () => {
      try {
        const token = authService.getToken()
        const refreshToken = authService.getRefreshToken()
        const userData = authService.getUserData()
        
        if (token && userData) {
          dispatch({
            type: AUTH_ACTIONS.LOAD_USER,
            payload: {
              user: userData,
              token,
              refreshToken,
              isAuthenticated: true
            }
          })
        } else {
          // Aucune session valide trouvée, nettoyer
          authService.clearAuth()
          dispatch({
            type: AUTH_ACTIONS.LOAD_USER,
            payload: {
              user: null,
              token: null,
              refreshToken: null,
              isAuthenticated: false
            }
          })
          // Note: La redirection est gérée par ProtectedRoute
        }
      } catch (error) {
        console.error('Erreur lors du chargement de la session:', error)
        authService.clearAuth()
        dispatch({
          type: AUTH_ACTIONS.LOAD_USER,
          payload: {
            user: null,
            token: null,
            refreshToken: null,
            isAuthenticated: false
          }
        })
      }
    }

    loadUserFromStorage()
  }, [])

  // Fonction de connexion
  const login = async (credentials) => {
    dispatch({ type: AUTH_ACTIONS.LOGIN_START })
    
    try {
      const response = await authService.login(credentials)
      
      if (response.success && response.data) {
        const userData = response.data
        
        dispatch({
          type: AUTH_ACTIONS.LOGIN_SUCCESS,
          payload: {
            user: userData,
            token: userData.access_token,
            refreshToken: userData.refresh_token
          }
        })
        
        return response
      } else {
        throw new Error(response.message || 'Erreur de connexion')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur de connexion'
      dispatch({
        type: AUTH_ACTIONS.LOGIN_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }

  // Fonction d'inscription
  const register = async (userData) => {
    dispatch({ type: AUTH_ACTIONS.REGISTER_START })
    
    try {
      const response = await authService.register(userData)
      
      if (response.success && response.data) {
        // Pour l'inscription, l'utilisateur n'est pas automatiquement connecté
        // Il doit vérifier son email d'abord
        dispatch({
          type: AUTH_ACTIONS.REGISTER_SUCCESS,
          payload: {
            user: null,
            token: null,
            refreshToken: null
          }
        })
        
        return response
      } else {
        throw new Error(response.message || 'Erreur d\'inscription')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur d\'inscription'
      dispatch({
        type: AUTH_ACTIONS.REGISTER_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }

  // Fonction de déconnexion
  const logout = async () => {
    try {
      await authService.logout()
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error)
    } finally {
      dispatch({ type: AUTH_ACTIONS.LOGOUT })
      // Rediriger vers la page de connexion
      if (window.location.pathname !== '/login') {
        window.location.href = '/login'
      }
    }
  }

  // Fonction pour récupérer le profil utilisateur
  const getCurrentUser = async () => {
    try {
      dispatch({ type: AUTH_ACTIONS.SET_LOADING, payload: true })
      const response = await authService.getCurrentUser()
      
      if (response.success && response.data) {
        dispatch({
          type: AUTH_ACTIONS.LOAD_USER,
          payload: {
            user: response.data,
            token: state.token,
            refreshToken: state.refreshToken,
            isAuthenticated: true
          }
        })
        return response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération du profil:', error)
      // Si l'erreur est due à un token invalide, déconnecter l'utilisateur
      if (error.status === 401) {
        logout()
      }
    } finally {
      dispatch({ type: AUTH_ACTIONS.SET_LOADING, payload: false })
    }
  }

  // Fonction pour effacer les erreurs
  const clearError = () => {
    dispatch({ type: AUTH_ACTIONS.CLEAR_ERROR })
  }

  // Vérifier la disponibilité d'un nom d'utilisateur
  const checkUsernameAvailability = async (username) => {
    try {
      return await authService.checkUsernameAvailability(username)
    } catch (error) {
      console.error('Erreur vérification nom d\'utilisateur:', error)
      throw error
    }
  }

  // Vérifier la disponibilité d'un email
  const checkEmailAvailability = async (email) => {
    try {
      return await authService.checkEmailAvailability(email)
    } catch (error) {
      console.error('Erreur vérification email:', error)
      throw error
    }
  }

  // Valeurs exposées par le contexte
  const value = {
    // État
    user: state.user,
    token: state.token,
    refreshToken: state.refreshToken,
    isAuthenticated: state.isAuthenticated,
    isLoading: state.isLoading,
    error: state.error,
    
    // Actions
    login,
    register,
    logout,
    getCurrentUser,
    clearError,
    checkUsernameAvailability,
    checkEmailAvailability
  }

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  )
}

export default AuthContext