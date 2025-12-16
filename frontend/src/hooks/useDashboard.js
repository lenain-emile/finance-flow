import { useState, useEffect, useCallback } from 'react'
import dashboardService from '@/services/dashboardService'
import transactionService from '@/services/transactionService'

/**
 * Hook principal pour le dashboard - agrège toutes les données
 */
export function useDashboard() {
  const [data, setData] = useState({
    accounts: [],
    recentTransactions: [],
    allMonthTransactions: [], // Toutes les transactions du mois pour calculs
    stats: {},
    budgets: [],
    budgetAlerts: [],
    dueTransactions: [],
    upcomingTransactions: []
  })
  const [chartData, setChartData] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState(null)

  const fetchDashboardData = useCallback(async () => {
    try {
      setIsLoading(true)
      setError(null)

      const result = await dashboardService.getDashboardData()

      if (result.success) {
        // Calculer le solde total actuel des comptes
        const totalBalance = dashboardService.calculateTotalBalance(result.data.accounts)
        
        // Récupérer plus de transactions pour le graphique ET les calculs mensuels
        let allTransactions = []
        try {
          const allTransactionsRes = await transactionService.getTransactions({ 
            limit: 100, 
            sort_by: 'date',
            sort_order: 'DESC'
          })
          allTransactions = allTransactionsRes.data?.transactions || []
          // Générer le graphique en partant du solde actuel et en remontant dans le temps
          const chart = dashboardService.generateBalanceChartData(allTransactions, totalBalance, 30)
          setChartData(chart)
        } catch {
          // Si erreur, générer données vides
          setChartData([])
        }

        // Mettre à jour les données avec toutes les transactions
        setData({
          ...result.data,
          allMonthTransactions: allTransactions
        })
      } else {
        setError(result.error)
      }
    } catch (err) {
      setError(err.message || 'Erreur lors du chargement du dashboard')
      console.error('Erreur useDashboard:', err)
    } finally {
      setIsLoading(false)
    }
  }, [])

  useEffect(() => {
    fetchDashboardData()
  }, [fetchDashboardData])

  // Calculs dérivés
  const totalBalance = dashboardService.calculateTotalBalance(data.accounts)
  
  // Calculer revenus et dépenses du mois depuis TOUTES les transactions
  const monthlyIncome = dashboardService.calculateMonthlyIncome(data.allMonthTransactions)
  const monthlyExpenses = dashboardService.calculateMonthlyExpenses(data.allMonthTransactions)
  
  const alertsCount = data.budgetAlerts.length
  const dueCount = data.dueTransactions.length

  return {
    // Données brutes
    accounts: data.accounts,
    recentTransactions: data.recentTransactions,
    budgets: data.budgets,
    budgetAlerts: data.budgetAlerts,
    dueTransactions: data.dueTransactions,
    upcomingTransactions: data.upcomingTransactions,
    chartData,
    
    // Données calculées
    totalBalance,
    monthlyIncome,
    monthlyExpenses,
    alertsCount,
    dueCount,
    
    // États
    isLoading,
    error,
    
    // Actions
    refetch: fetchDashboardData
  }
}

export default useDashboard
