import { createContext, useReducer, useCallback } from 'react'
import transactionService from '@/services/transactionService'

// Actions pour le reducer des transactions
const TRANSACTION_ACTIONS = {
  FETCH_TRANSACTIONS_START: 'FETCH_TRANSACTIONS_START',
  FETCH_TRANSACTIONS_SUCCESS: 'FETCH_TRANSACTIONS_SUCCESS',
  FETCH_TRANSACTIONS_ERROR: 'FETCH_TRANSACTIONS_ERROR',
  
  ADD_TRANSACTION_START: 'ADD_TRANSACTION_START',
  ADD_TRANSACTION_SUCCESS: 'ADD_TRANSACTION_SUCCESS',
  ADD_TRANSACTION_ERROR: 'ADD_TRANSACTION_ERROR',
  
  UPDATE_TRANSACTION_START: 'UPDATE_TRANSACTION_START',
  UPDATE_TRANSACTION_SUCCESS: 'UPDATE_TRANSACTION_SUCCESS',
  UPDATE_TRANSACTION_ERROR: 'UPDATE_TRANSACTION_ERROR',
  
  DELETE_TRANSACTION_START: 'DELETE_TRANSACTION_START',
  DELETE_TRANSACTION_SUCCESS: 'DELETE_TRANSACTION_SUCCESS',
  DELETE_TRANSACTION_ERROR: 'DELETE_TRANSACTION_ERROR',
  
  CLEAR_ERROR: 'CLEAR_ERROR',
  SET_LOADING: 'SET_LOADING'
}

// État initial
const initialState = {
  transactions: [],
  currentTransaction: null,
  totalAmount: 0,
  stats: null,
  isLoading: false,
  error: null
}

// Reducer pour gérer l'état des transactions
function transactionReducer(state, action) {
  switch (action.type) {
    case TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_START:
    case TRANSACTION_ACTIONS.ADD_TRANSACTION_START:
    case TRANSACTION_ACTIONS.UPDATE_TRANSACTION_START:
    case TRANSACTION_ACTIONS.DELETE_TRANSACTION_START:
      return {
        ...state,
        isLoading: true,
        error: null
      }

    case TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_SUCCESS:
      return {
        ...state,
        transactions: action.payload.transactions || action.payload,
        totalAmount: action.payload.total || 0,
        isLoading: false,
        error: null
      }

    case TRANSACTION_ACTIONS.ADD_TRANSACTION_SUCCESS:
      return {
        ...state,
        transactions: [action.payload, ...state.transactions],
        isLoading: false,
        error: null
      }

    case TRANSACTION_ACTIONS.UPDATE_TRANSACTION_SUCCESS:
      return {
        ...state,
        transactions: state.transactions.map(transaction =>
          transaction.id === action.payload.id ? action.payload : transaction
        ),
        currentTransaction: action.payload,
        isLoading: false,
        error: null
      }

    case TRANSACTION_ACTIONS.DELETE_TRANSACTION_SUCCESS:
      return {
        ...state,
        transactions: state.transactions.filter(transaction =>
          transaction.id !== action.payload.id
        ),
        isLoading: false,
        error: null
      }

    case TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR:
    case TRANSACTION_ACTIONS.ADD_TRANSACTION_ERROR:
    case TRANSACTION_ACTIONS.UPDATE_TRANSACTION_ERROR:
    case TRANSACTION_ACTIONS.DELETE_TRANSACTION_ERROR:
      return {
        ...state,
        isLoading: false,
        error: action.payload
      }

    case TRANSACTION_ACTIONS.CLEAR_ERROR:
      return {
        ...state,
        error: null
      }

    case TRANSACTION_ACTIONS.SET_LOADING:
      return {
        ...state,
        isLoading: action.payload
      }

    default:
      return state
  }
}

// Création du contexte
const TransactionContext = createContext()

// Provider du contexte des transactions
export function TransactionProvider({ children }) {
  const [state, dispatch] = useReducer(transactionReducer, initialState)

  // Récupérer toutes les transactions
  const fetchTransactions = useCallback(async (params = {}) => {
    dispatch({ type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_START })
    
    try {
      const response = await transactionService.getTransactions(params)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_SUCCESS,
          payload: response.data
        })
        return response
      } else {
        throw new Error(response.message || 'Erreur lors de la récupération des transactions')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de la récupération des transactions'
      dispatch({
        type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Ajouter une nouvelle transaction
  const addTransaction = useCallback(async (transactionData) => {
    dispatch({ type: TRANSACTION_ACTIONS.ADD_TRANSACTION_START })
    
    try {
      const response = await transactionService.createTransaction(transactionData)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.ADD_TRANSACTION_SUCCESS,
          payload: response.data
        })
        return response
      } else {
        throw new Error(response.message || 'Erreur lors de l\'ajout de la transaction')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de l\'ajout de la transaction'
      dispatch({
        type: TRANSACTION_ACTIONS.ADD_TRANSACTION_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Mettre à jour une transaction
  const updateTransaction = useCallback(async (id, transactionData) => {
    dispatch({ type: TRANSACTION_ACTIONS.UPDATE_TRANSACTION_START })
    
    try {
      const response = await transactionService.updateTransaction(id, transactionData)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.UPDATE_TRANSACTION_SUCCESS,
          payload: response.data
        })
        return response
      } else {
        throw new Error(response.message || 'Erreur lors de la mise à jour de la transaction')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de la mise à jour de la transaction'
      dispatch({
        type: TRANSACTION_ACTIONS.UPDATE_TRANSACTION_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Supprimer une transaction
  const deleteTransaction = useCallback(async (id) => {
    dispatch({ type: TRANSACTION_ACTIONS.DELETE_TRANSACTION_START })
    
    try {
      const response = await transactionService.deleteTransaction(id)
      
      if (response.success) {
        dispatch({
          type: TRANSACTION_ACTIONS.DELETE_TRANSACTION_SUCCESS,
          payload: { id }
        })
        return response
      } else {
        throw new Error(response.message || 'Erreur lors de la suppression de la transaction')
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de la suppression de la transaction'
      dispatch({
        type: TRANSACTION_ACTIONS.DELETE_TRANSACTION_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Récupérer les transactions récentes
  const fetchRecentTransactions = useCallback(async (limit = 10) => {
    try {
      dispatch({ type: TRANSACTION_ACTIONS.SET_LOADING, payload: true })
      const response = await transactionService.getRecentTransactions(limit)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_SUCCESS,
          payload: response.data
        })
        return response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des transactions récentes:', error)
      dispatch({
        type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR,
        payload: error.message
      })
    } finally {
      dispatch({ type: TRANSACTION_ACTIONS.SET_LOADING, payload: false })
    }
  }, [])

  // Récupérer les statistiques des transactions
  const fetchTransactionStats = useCallback(async () => {
    try {
      dispatch({ type: TRANSACTION_ACTIONS.SET_LOADING, payload: true })
      const response = await transactionService.getTransactionStats()
      
      if (response.success && response.data) {
        return response.data
      }
    } catch (error) {
      console.error('Erreur lors de la récupération des statistiques:', error)
      dispatch({
        type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR,
        payload: error.message
      })
    } finally {
      dispatch({ type: TRANSACTION_ACTIONS.SET_LOADING, payload: false })
    }
  }, [])

  // Rechercher des transactions
  const searchTransactions = useCallback(async (searchTerm) => {
    dispatch({ type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_START })
    
    try {
      const response = await transactionService.searchTransactions(searchTerm)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_SUCCESS,
          payload: response.data
        })
        return response
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de la recherche'
      dispatch({
        type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Récupérer les transactions par période
  const fetchTransactionsByDateRange = useCallback(async (startDate, endDate) => {
    dispatch({ type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_START })
    
    try {
      const response = await transactionService.getTransactionsByDateRange(startDate, endDate)
      
      if (response.success && response.data) {
        dispatch({
          type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_SUCCESS,
          payload: response.data
        })
        return response
      }
    } catch (error) {
      const errorMessage = error.message || 'Erreur lors de la récupération par période'
      dispatch({
        type: TRANSACTION_ACTIONS.FETCH_TRANSACTIONS_ERROR,
        payload: errorMessage
      })
      throw error
    }
  }, [])

  // Calculer le total des transactions
  const calculateTotal = useCallback(async (startDate = null, endDate = null) => {
    try {
      const response = await transactionService.getTotalAmount(startDate, endDate)
      
      if (response.success && response.data) {
        return response.data
      }
    } catch (error) {
      console.error('Erreur lors du calcul du total:', error)
      throw error
    }
  }, [])

  // Fonction pour effacer les erreurs
  const clearError = useCallback(() => {
    dispatch({ type: TRANSACTION_ACTIONS.CLEAR_ERROR })
  }, [])

  // Valeurs exposées par le contexte
  const value = {
    // État
    transactions: state.transactions,
    currentTransaction: state.currentTransaction,
    totalAmount: state.totalAmount,
    stats: state.stats,
    isLoading: state.isLoading,
    error: state.error,
    
    // Actions
    fetchTransactions,
    addTransaction,
    updateTransaction,
    deleteTransaction,
    fetchRecentTransactions,
    fetchTransactionStats,
    searchTransactions,
    fetchTransactionsByDateRange,
    calculateTotal,
    clearError,
    
    // Utilitaires
    formatAmount: transactionService.formatAmount,
    formatDate: transactionService.formatDate,
    getCurrentDate: transactionService.getCurrentDate
  }

  return (
    <TransactionContext.Provider value={value}>
      {children}
    </TransactionContext.Provider>
  )
}

export default TransactionContext