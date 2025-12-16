import { useState, useEffect, useCallback } from 'react'
import accountService from '@/services/accountService'

/**
 * Hook pour la gestion des comptes
 */
export function useAccounts() {
  const [accounts, setAccounts] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchAccounts = useCallback(async () => {
    try {
      setIsLoading(true)
      setError(null)
      const response = await accountService.getAll()
      setAccounts(response.data?.accounts || [])
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des comptes')
      console.error('Erreur useAccounts:', err)
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchAccounts()
  }, [fetchAccounts])

  const totalBalance = accounts.reduce((total, account) => {
    return total + (parseFloat(account.current_balance) || 0)
  }, 0)

  const createAccount = async (data) => {
    const response = await accountService.create(data)
    await fetchAccounts()
    return response
  }

  const updateAccount = async (id, data) => {
    const response = await accountService.update(id, data)
    await fetchAccounts()
    return response
  }

  const deleteAccount = async (id) => {
    const response = await accountService.delete(id)
    await fetchAccounts()
    return response
  }

  return {
    accounts,
    totalBalance,
    isLoading,
    error,
    refetch: fetchAccounts,
    createAccount,
    updateAccount,
    deleteAccount
  }
}

export default useAccounts
