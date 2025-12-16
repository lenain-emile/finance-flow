import { useState, useEffect, useCallback } from 'react'
import plannedTransactionService from '@/services/plannedTransactionService'

/**
 * Hook pour la gestion des transactions planifiées
 */
export function usePlannedTransactions() {
  const [plannedTransactions, setPlannedTransactions] = useState([])
  const [dueTransactions, setDueTransactions] = useState([])
  const [upcomingTransactions, setUpcomingTransactions] = useState([])
  const [stats, setStats] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchAll = useCallback(async () => {
    try {
      setIsLoading(true)
      setError(null)

      const [allRes, dueRes, upcomingRes] = await Promise.all([
        plannedTransactionService.getAll(true),
        plannedTransactionService.getDue(),
        plannedTransactionService.getUpcoming(7)
      ])

      setPlannedTransactions(allRes.data?.planned_transactions || [])
      setDueTransactions(dueRes.data?.due_transactions || [])
      setUpcomingTransactions(upcomingRes.data?.upcoming_transactions || [])
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des transactions planifiées')
      console.error('Erreur usePlannedTransactions:', err)
    } finally {
      setIsLoading(false)
    }
  }, [])

  const fetchStats = useCallback(async () => {
    try {
      const response = await plannedTransactionService.getStats()
      setStats(response.data)
    } catch (err) {
      console.error('Erreur fetchStats:', err)
    }
  }, [])

  useEffect(() => {
    fetchAll()
  }, [fetchAll])

  const dueCount = dueTransactions.length
  const upcomingCount = upcomingTransactions.length

  const executeTransaction = async (id) => {
    const response = await plannedTransactionService.execute(id)
    await fetchAll()
    return response
  }

  const executeAllDue = async () => {
    const response = await plannedTransactionService.executeAll()
    await fetchAll()
    return response
  }

  const toggleTransaction = async (id) => {
    const response = await plannedTransactionService.toggle(id)
    await fetchAll()
    return response
  }

  const createPlannedTransaction = async (data) => {
    const response = await plannedTransactionService.create(data)
    await fetchAll()
    return response
  }

  const updatePlannedTransaction = async (id, data) => {
    const response = await plannedTransactionService.update(id, data)
    await fetchAll()
    return response
  }

  const deletePlannedTransaction = async (id) => {
    const response = await plannedTransactionService.delete(id)
    await fetchAll()
    return response
  }

  return {
    plannedTransactions,
    dueTransactions,
    upcomingTransactions,
    stats,
    dueCount,
    upcomingCount,
    isLoading,
    error,
    refetch: fetchAll,
    fetchStats,
    executeTransaction,
    executeAllDue,
    toggleTransaction,
    createPlannedTransaction,
    updatePlannedTransaction,
    deletePlannedTransaction
  }
}

export default usePlannedTransactions
