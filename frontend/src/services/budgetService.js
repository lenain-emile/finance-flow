import api from './api'

/**
 * Service pour la gestion des budgets
 */
const budgetService = {
  /**
   * Récupérer tous les budgets
   * @param {boolean} withUsage - Inclure les données d'utilisation
   */
  async getAll(withUsage = false) {
    const params = withUsage ? '?with_usage=true' : ''
    const response = await api.get(`/api/budgets${params}`)
    return response.data
  },

  /**
   * Récupérer un budget spécifique
   */
  async getById(id) {
    const response = await api.get(`/api/budgets/${id}`)
    return response.data
  },

  /**
   * Récupérer les budgets en alerte (>= 80%)
   */
  async getAlerts() {
    const response = await api.get('/api/budgets/alerts')
    return response.data
  },

  /**
   * Récupérer les budgets dépassés
   */
  async getExceeded() {
    const response = await api.get('/api/budgets/exceeded')
    return response.data
  },

  /**
   * Récupérer les statistiques des budgets
   */
  async getStats() {
    const response = await api.get('/api/budgets/stats')
    return response.data
  },

  /**
   * Créer un nouveau budget
   */
  async create(data) {
    const response = await api.post('/api/budgets', data)
    return response.data
  },

  /**
   * Mettre à jour un budget
   */
  async update(id, data) {
    const response = await api.put(`/api/budgets/${id}`, data)
    return response.data
  },

  /**
   * Supprimer un budget
   */
  async delete(id) {
    const response = await api.delete(`/api/budgets/${id}`)
    return response.data
  },

  /**
   * Vérifier l'impact d'une transaction sur un budget
   */
  async checkImpact(categoryId, amount) {
    const response = await api.post('/api/budgets/check-impact', {
      category_id: categoryId,
      amount
    })
    return response.data
  }
}

export default budgetService
