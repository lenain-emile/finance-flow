import api from './api'

/**
 * Service pour la gestion des catégories
 */
const categoryService = {
  /**
   * Récupérer les catégories de dépenses (pour les budgets)
   */
  async getExpenses() {
    const response = await api.get('/api/categories/expenses')
    return response.data
  },

  /**
   * Récupérer les catégories de revenus
   */
  async getIncomes() {
    const response = await api.get('/api/categories/incomes')
    return response.data
  }
}

export default categoryService
