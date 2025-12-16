import { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { Wallet, TrendingUp, TrendingDown, AlertCircle, RefreshCw, Trash2 } from 'lucide-react';
import {
  Navbar,
  StatCard,
  BalanceChart,
  AccountsList,
  QuickActions,
  TransactionsModal,
  BudgetsModal,
  PlannedTransactionsModal
} from '@/components/molecules';
import { useDashboard } from '@/hooks/useDashboard'
import { usePlannedTransactions } from '@/hooks/usePlannedTransactions'
import transactionService from '@/services/transactionService'
import plannedTransactionService from '@/services/plannedTransactionService'
import budgetService from '@/services/budgetService'
import accountService from '@/services/accountService'
import '@/styles/components/dashboard.css'

export function DashboardPage() {
  const navigate = useNavigate()
  const location = useLocation()
  
  // États pour les modales
  const [showTransactions, setShowTransactions] = useState(false)
  const [showBudgets, setShowBudgets] = useState(false)
  const [showPlanned, setShowPlanned] = useState(false)

  // Hook principal du dashboard
  const {
    accounts,
    recentTransactions,
    budgets,
    dueTransactions,
    upcomingTransactions,
    chartData,
    totalBalance,
    monthlyIncome,
    monthlyExpenses,
    isLoading,
    error,
    refetch
  } = useDashboard()

  // Hook pour les actions sur transactions planifiées
  const { executeTransaction, executeAllDue } = usePlannedTransactions()

  // Rafraîchir les données si on revient d'une création
  useEffect(() => {
    if (location.state?.refresh) {
      refetch()
      // Nettoyer le state pour éviter les rafraîchissements multiples
      window.history.replaceState({}, document.title)
    }
  }, [location.state, refetch])

  // Handlers
  const handleAddTransaction = () => {
    navigate('/add-transaction')
  }

  const handleViewStats = () => {
    console.log('View stats')
  }

  const handleSettings = () => {
    console.log('Settings')
  }

  const handleExecuteTransaction = async (id) => {
    try {
      await executeTransaction(id)
      refetch()
    } catch (err) {
      console.error('Erreur exécution:', err)
    }
  }

  const handleExecuteAll = async () => {
    try {
      await executeAllDue()
      refetch()
    } catch (err) {
      console.error('Erreur exécution:', err)
    }
  }

  const handleDeleteTransaction = async (id, e) => {
    e.stopPropagation()
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette transaction ?')) return
    
    try {
      await transactionService.deleteTransaction(id)
      refetch()
    } catch (err) {
      console.error('Erreur suppression transaction:', err)
      alert('Erreur lors de la suppression de la transaction')
    }
  }

  const handleDeletePlannedTransaction = async (id, e) => {
    e.stopPropagation()
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette transaction planifiée ?')) return
    
    try {
      await plannedTransactionService.delete(id)
      refetch()
    } catch (err) {
      console.error('Erreur suppression transaction planifiée:', err)
      alert('Erreur lors de la suppression de la transaction planifiée')
    }
  }

  const handleDeleteBudget = async (id, e) => {
    e.stopPropagation()
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce budget ?')) return
    
    try {
      await budgetService.delete(id)
      refetch()
    } catch (err) {
      console.error('Erreur suppression budget:', err)
      alert('Erreur lors de la suppression du budget')
    }
  }

  const handleDeleteAccount = async (account) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer le compte "${account.name}" ?`)) return
    
    try {
      await accountService.delete(account.id)
      refetch()
    } catch (err) {
      console.error('Erreur suppression compte:', err)
      alert('Erreur lors de la suppression du compte')
    }
  }

  // État de chargement
  if (isLoading) {
    return (
      <div className="dashboard">
        <Navbar currentPage="dashboard" />
        <div className="dashboard__loading">
          <div className="dashboard__spinner" />
        </div>
      </div>
    )
  }

  // État d'erreur
  if (error) {
    return (
      <div className="dashboard">
        <Navbar currentPage="dashboard" />
        <div className="dashboard__error">
          <AlertCircle className="dashboard__error-icon" />
          <p>Erreur lors du chargement des données</p>
          <button 
            onClick={refetch}
            className="flex items-center gap-2 px-4 py-2 bg-[#C38EF0] text-white rounded-lg hover:opacity-90"
          >
            <RefreshCw className="h-4 w-4" />
            Réessayer
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="dashboard">
      <Navbar currentPage="dashboard" />
      
      <div className="dashboard__content">
        {/* Ligne des statistiques */}
        <div className="dashboard__stats-row">
          <StatCard
            title="Solde total"
            value={totalBalance}
            icon={Wallet}
            variant="default"
          />
          <StatCard
            title="Revenus du mois"
            value={monthlyIncome}
            icon={TrendingUp}
            variant="income"
            trend="up"
          />
          <StatCard
            title="Dépenses du mois"
            value={monthlyExpenses}
            icon={TrendingDown}
            variant="expense"
          />
          <QuickActions
            onAddTransaction={handleAddTransaction}
            onViewStats={handleViewStats}
            onSettings={handleSettings}
          />
        </div>

        {/* Grille principale */}
        <div className="dashboard__main-grid">
          {/* Colonne gauche - Graphique */}
          <div className="dashboard__left-column">
            <div className="dashboard__chart">
              <BalanceChart data={chartData} />
            </div>
            {/* Section Transactions + Planifiées */}
            <div className="dashboard__bottom-section">
              {/* Transactions récentes */}
              <div className="dashboard__card">
                <div className="dashboard__card-header">
                  <h3 className="dashboard__card-title">Transactions récentes</h3>
                  <button 
                    onClick={() => setShowTransactions(true)}
                    className="dashboard__card-action"
                  >
                    Voir tout
                  </button>
                </div>
                <div className="dashboard__card-content">
                  {recentTransactions.length > 0 ? (
                    <div className="space-y-2">
                      {recentTransactions.slice(0, 3).map((transaction) => (
                        <div 
                          key={transaction.id}
                          className="flex items-center gap-2 p-2 rounded hover:bg-white/50 transition-colors group"
                        >
                          <div className={`w-2 h-2 rounded-full ${parseFloat(transaction.amount) >= 0 ? 'bg-green-500' : 'bg-red-500'}`} />
                          <div 
                            className="flex-1 min-w-0 cursor-pointer"
                            onClick={() => setShowTransactions(true)}
                          >
                            <p className="text-sm font-medium truncate">{transaction.title}</p>
                            <p className="text-xs text-gray-600 truncate">
                              {new Date(transaction.date).toLocaleDateString('fr-FR')}
                            </p>
                          </div>
                          <span className={`text-sm font-semibold ${parseFloat(transaction.amount) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                            {parseFloat(transaction.amount) >= 0 ? '+' : ''}{parseFloat(transaction.amount).toFixed(2)} €
                          </span>
                          <button
                            onClick={(e) => handleDeleteTransaction(transaction.id, e)}
                            className="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 rounded transition-all"
                            title="Supprimer"
                          >
                            <Trash2 className="h-4 w-4 text-red-600" />
                          </button>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500 text-center py-4">Aucune transaction</p>
                  )}
                </div>
              </div>

              {/* Transactions planifiées */}
              <div className="dashboard__card">
                <div className="dashboard__card-header">
                  <h3 className="dashboard__card-title">Transactions planifiées</h3>
                  <button 
                    onClick={() => setShowPlanned(true)}
                    className="dashboard__card-action"
                  >
                    Voir tout
                  </button>
                </div>
                <div className="dashboard__card-content">
                  {dueTransactions.length > 0 || upcomingTransactions.length > 0 ? (
                    <div className="space-y-2">
                      {dueTransactions.slice(0, 2).map((transaction) => (
                        <div 
                          key={transaction.id}
                          className="flex items-center gap-2 p-2 rounded bg-red-50 border border-red-200 group"
                        >
                          <AlertCircle className="h-4 w-4 text-red-600 shrink-0" />
                          <div 
                            className="flex-1 min-w-0 cursor-pointer"
                            onClick={() => setShowPlanned(true)}
                          >
                            <p className="text-sm font-medium truncate">{transaction.title}</p>
                            <p className="text-xs text-gray-600 truncate">
                              {new Date(transaction.next_date).toLocaleDateString('fr-FR')}
                            </p>
                          </div>
                          <span className={`text-sm font-semibold ${transaction.operation_type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                            {transaction.operation_type === 'income' ? '+' : '-'}{Math.abs(parseFloat(transaction.amount)).toFixed(2)} €
                          </span>
                          <button
                            onClick={(e) => handleDeletePlannedTransaction(transaction.id, e)}
                            className="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 rounded transition-all"
                            title="Supprimer"
                          >
                            <Trash2 className="h-4 w-4 text-red-600" />
                          </button>
                        </div>
                      ))}
                      {upcomingTransactions.slice(0, dueTransactions.length > 0 ? 1 : 3).map((transaction) => (
                        <div 
                          key={transaction.id}
                          className="flex items-center gap-2 p-2 rounded hover:bg-white/50 transition-colors group"
                        >
                          <div className={`w-2 h-2 rounded-full ${transaction.operation_type === 'income' ? 'bg-green-500' : 'bg-red-500'}`} />
                          <div 
                            className="flex-1 min-w-0 cursor-pointer"
                            onClick={() => setShowPlanned(true)}
                          >
                            <p className="text-sm font-medium truncate">{transaction.title}</p>
                            <p className="text-xs text-gray-600 truncate">
                              {new Date(transaction.next_date).toLocaleDateString('fr-FR')}
                            </p>
                          </div>
                          <span className={`text-sm font-semibold ${transaction.operation_type === 'income' ? 'text-green-600' : 'text-red-600'}`}>
                            {transaction.operation_type === 'income' ? '+' : '-'}{Math.abs(parseFloat(transaction.amount)).toFixed(2)} €
                          </span>
                          <button
                            onClick={(e) => handleDeletePlannedTransaction(transaction.id, e)}
                            className="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 rounded transition-all"
                            title="Supprimer"
                          >
                            <Trash2 className="h-4 w-4 text-red-600" />
                          </button>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="text-sm text-gray-500 text-center py-4">Aucune transaction planifiée</p>
                  )}
                </div>
              </div>
            </div>
          </div>
          
          {/* Colonne droite - Comptes + Budgets */}
          <div className="dashboard__right-column">
            <div className="dashboard__accounts">
              <AccountsList 
                accounts={accounts}
                onAddAccount={() => navigate('/accounts/create')}
                onSelectAccount={(account) => console.log('Select account', account)}
                onDeleteAccount={handleDeleteAccount}
              />
            </div>
            
            {/* Budgets */}
            <div className="dashboard__card">
              <div className="dashboard__card-header">
                <h3 className="dashboard__card-title">Budgets</h3>
                <button 
                  onClick={() => setShowBudgets(true)}
                  className="dashboard__card-action"
                >
                  Voir tout
                </button>
              </div>
              <div className="dashboard__card-content">
                {budgets.length > 0 ? (
                  <div className="space-y-3">
                    {budgets.slice(0, 3).map((budget) => {
                      const percentage = budget.percentage || (budget.spent_amount / budget.max_amount) * 100 || 0
                      const cappedPercentage = Math.min(percentage, 100)
                      return (
                        <div 
                          key={budget.id}
                          className="p-3 rounded border hover:border-[#C38EF0]/50 transition-colors group"
                        >
                          <div className="flex items-center justify-between mb-2">
                            <span 
                              className="text-sm font-medium truncate cursor-pointer flex-1"
                              onClick={() => setShowBudgets(true)}
                            >
                              {budget.category_name || `Budget ${budget.id}`}
                            </span>
                            <div className="flex items-center gap-2">
                              <span className={`text-xs font-semibold ${percentage >= 100 ? 'text-red-600' : percentage >= 80 ? 'text-orange-600' : 'text-green-600'}`}>
                                {Math.round(percentage)}%
                              </span>
                              <button
                                onClick={(e) => handleDeleteBudget(budget.id, e)}
                                className="opacity-0 group-hover:opacity-100 p-1 hover:bg-red-100 rounded transition-all"
                                title="Supprimer"
                              >
                                <Trash2 className="h-3 w-3 text-red-600" />
                              </button>
                            </div>
                          </div>
                          <div className="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div 
                              className={`h-full transition-all ${percentage >= 100 ? 'bg-red-500' : percentage >= 80 ? 'bg-orange-500' : 'bg-green-500'}`}
                              style={{ width: `${cappedPercentage}%` }}
                            />
                          </div>
                          <div className="flex justify-between text-xs text-gray-600 mt-1">
                            <span>{(budget.spent_amount || 0).toFixed(2)} €</span>
                            <span>{budget.max_amount.toFixed(2)} €</span>
                          </div>
                        </div>
                      )
                    })}
                  </div>
                ) : (
                  <p className="text-sm text-gray-500 text-center py-4">Aucun budget défini</p>
                )}
              </div>
            </div>
          </div>
        </div>

      </div>

      {/* Modales */}
      <TransactionsModal
        open={showTransactions}
        onOpenChange={setShowTransactions}
        transactions={recentTransactions}
      />

      <BudgetsModal
        open={showBudgets}
        onOpenChange={setShowBudgets}
        budgets={budgets}
      />

      <PlannedTransactionsModal
        open={showPlanned}
        onOpenChange={setShowPlanned}
        dueTransactions={dueTransactions}
        upcomingTransactions={upcomingTransactions}
        onExecute={handleExecuteTransaction}
        onExecuteAll={handleExecuteAll}
      />
    </div>
  )
}

export default DashboardPage
