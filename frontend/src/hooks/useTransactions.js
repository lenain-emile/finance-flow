import { useContext } from 'react'
import TransactionContext from '../contexts/TransactionContext'

/**
 * Hook pour utiliser le contexte des transactions
 * Suit le même pattern qu'useAuth
 */
export function useTransactions() {
  const context = useContext(TransactionContext)

  if (!context) {
    throw new Error('useTransactions must be used within a TransactionProvider')
  }

  return context
}

export default useTransactions