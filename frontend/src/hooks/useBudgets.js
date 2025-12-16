import { useState, useEffect, useCallback } from 'react'
import budgetService from '@/services/budgetService'

/**
 * Hook pour la gestion des budgets
 */
export function useBudgets(withUsage = true) {
  const [budgets, setBudgets] = useState([])
  const [alerts, setAlerts] = useState([])
  const [stats, setStats] = useState(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchBudgets = useCallback(async () => {
    try {
      setIsLoading(true)
      setError(null)
      
      const [budgetsRes, alertsRes] = await Promise.all([
        budgetService.getAll(withUsage),
        budgetService.getAlerts()
      ])

      setBudgets(budgetsRes.data?.budgets || [])
      setAlerts(alertsRes.data?.budgets || [])
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement des budgets')
      console.error('Erreur useBudgets:', err)
    } finally {
      setIsLoading(false)
    }
  }, [withUsage])

  const fetchStats = useCallback(async () => {
    try {
      const response = await budgetService.getStats()
      setStats(response.data)
    } catch (err) {
      console.error('Erreur fetchStats:', err)
    }
  }, [])

  useEffect(() => {
    fetchBudgets()
  }, [fetchBudgets])

  const alertCount = alerts.length
  const exceededCount = budgets.filter(b => b.status === 'exceeded').length
  const warningCount = budgets.filter(b => b.status === 'warning').length

  const createBudget = async (data) => {
    const response = await budgetService.create(data)
    await fetchBudgets()
    return response
  }

  const updateBudget = async (id, data) => {
    const response = await budgetService.update(id, data)
    await fetchBudgets()
    return response
  }

  const deleteBudget = async (id) => {
    const response = await budgetService.delete(id)
    await fetchBudgets()
    return response
  }

  return {
    budgets,
    alerts,
    stats,
    alertCount,
    exceededCount,
    warningCount,
    isLoading,
    error,
    refetch: fetchBudgets,
    fetchStats,
    createBudget,
    updateBudget,
    deleteBudget
  }
}

export default useBudgets
