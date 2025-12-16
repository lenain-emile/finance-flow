import accountService from './accountService'
import budgetService from './budgetService'
import plannedTransactionService from './plannedTransactionService'
import transactionService from './transactionService'

/**
 * Service pour agréger les données du dashboard
 * Gère les erreurs individuellement pour permettre un affichage partiel
 */
const dashboardService = {
  /**
   * Récupérer toutes les données nécessaires au dashboard
   * Chaque appel est indépendant pour éviter qu'une erreur bloque tout
   */
  async getDashboardData() {
    const results = {
      accounts: [],
      recentTransactions: [],
      stats: {},
      budgets: [],
      budgetAlerts: [],
      dueTransactions: [],
      upcomingTransactions: []
    }

    // Récupérer les comptes
    try {
      const accountsRes = await accountService.getAll()
      results.accounts = accountsRes.data?.accounts || []
    } catch (error) {
      console.warn('Erreur chargement comptes:', error.message)
    }

    // Récupérer les transactions récentes
    try {
      const transactionsRes = await transactionService.getTransactions({ limit: 5 })
      // La réponse peut être dans .data.transactions ou .transactions selon la structure
      results.recentTransactions = transactionsRes.data?.transactions || transactionsRes.transactions || []
    } catch (error) {
      console.warn('Erreur chargement transactions:', error.message)
    }

    // Récupérer les stats (peut échouer si pas de transactions)
    try {
      const statsRes = await transactionService.getTransactionStats()
      results.stats = statsRes.data || {}
    } catch (error) {
      console.warn('Erreur chargement stats:', error.message)
      // Stats par défaut
      results.stats = {
        total_amount: 0,
        total_count: 0,
        monthly_amount: 0,
        average_amount: 0
      }
    }

    // Récupérer les budgets
    try {
      const budgetsRes = await budgetService.getAll(true)
      results.budgets = budgetsRes.data?.budgets || []
    } catch (error) {
      console.warn('Erreur chargement budgets:', error.message)
    }

    // Récupérer les alertes budgets
    try {
      const alertsRes = await budgetService.getAlerts()
      results.budgetAlerts = alertsRes.data?.budgets || []
    } catch (error) {
      console.warn('Erreur chargement alertes budgets:', error.message)
    }

    // Récupérer les transactions planifiées dues
    try {
      const dueRes = await plannedTransactionService.getDue()
      results.dueTransactions = dueRes.data?.due_transactions || []
    } catch (error) {
      console.warn('Erreur chargement transactions dues:', error.message)
    }

    // Récupérer les transactions planifiées à venir
    try {
      const upcomingRes = await plannedTransactionService.getUpcoming(7)
      results.upcomingTransactions = upcomingRes.data?.upcoming_transactions || []
    } catch (error) {
      console.warn('Erreur chargement transactions à venir:', error.message)
    }

    return {
      success: true,
      data: results
    }
  },

  /**
   * Calculer le solde total de tous les comptes
   */
  calculateTotalBalance(accounts) {
    return accounts.reduce((total, account) => {
      return total + (parseFloat(account.current_balance) || 0)
    }, 0)
  },

  /**
   * Calculer les revenus du mois
   */
  calculateMonthlyIncome(transactions) {
    const now = new Date()
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
    
    return transactions
      .filter(t => {
        const date = new Date(t.date)
        return date >= startOfMonth && parseFloat(t.amount) > 0
      })
      .reduce((total, t) => total + parseFloat(t.amount), 0)
  },

  /**
   * Calculer les dépenses du mois
   */
  calculateMonthlyExpenses(transactions) {
    const now = new Date()
    const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)
    
    return transactions
      .filter(t => {
        const date = new Date(t.date)
        return date >= startOfMonth && parseFloat(t.amount) < 0
      })
      .reduce((total, t) => total + Math.abs(parseFloat(t.amount)), 0)
  },

  /**
   * Générer les données pour le graphique d'évolution du solde
   * Part du solde actuel (currentBalance) et remonte dans le temps en soustrayant les transactions
   */
  generateBalanceChartData(transactions, currentBalance, days = 30) {
    const data = []
    const now = new Date()
    
    // Créer un map des transactions par date
    const transactionsByDate = {}
    transactions.forEach(t => {
      const dateKey = t.date.split('T')[0]
      if (!transactionsByDate[dateKey]) {
        transactionsByDate[dateKey] = 0
      }
      transactionsByDate[dateKey] += parseFloat(t.amount)
    })

    // On part du solde actuel et on remonte dans le temps
    // Pour chaque jour passé, on soustrait les transactions de ce jour
    // Cela nous donne le solde qu'on avait AVANT ces transactions
    
    // D'abord, calculer le solde pour chaque jour en partant d'aujourd'hui
    const balanceByDay = []
    let runningBalance = currentBalance
    
    for (let i = 0; i < days; i++) {
      const date = new Date(now)
      date.setDate(date.getDate() - i)
      const dateKey = date.toISOString().split('T')[0]
      
      // Stocker le solde du jour
      balanceByDay.unshift({
        date: dateKey,
        dateFormatted: date.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }),
        solde: Math.round(runningBalance * 100) / 100
      })
      
      // Soustraire les transactions de ce jour pour obtenir le solde de la veille
      if (transactionsByDate[dateKey]) {
        runningBalance -= transactionsByDate[dateKey]
      }
    }

    return balanceByDay
  }
}

export default dashboardService
