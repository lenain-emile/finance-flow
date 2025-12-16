import api from './api'

/**
 * Service pour la gestion des transactions planifiées/récurrentes
 */
const plannedTransactionService = {
  /**
   * Récupérer toutes les transactions planifiées
   * @param {boolean} activeOnly - Uniquement les actives
   */
  async getAll(activeOnly = false) {
    const params = activeOnly ? '?active=true' : ''
    const response = await api.get(`/api/planned-transactions${params}`)
    return response.data
  },

  /**
   * Récupérer une transaction planifiée spécifique
   */
  async getById(id) {
    const response = await api.get(`/api/planned-transactions/${id}`)
    return response.data
  },

  /**
   * Récupérer les transactions dues (à exécuter)
   */
  async getDue() {
    const response = await api.get('/api/planned-transactions/due')
    return response.data
  },

  /**
   * Récupérer les transactions à venir
   * @param {number} days - Nombre de jours à l'avance (défaut: 30)
   */
  async getUpcoming(days = 7) {
    const response = await api.get(`/api/planned-transactions/upcoming?days=${days}`)
    return response.data
  },

  /**
   * Récupérer les revenus planifiés
   */
  async getIncomes() {
    const response = await api.get('/api/planned-transactions/incomes')
    return response.data
  },

  /**
   * Récupérer les dépenses planifiées
   */
  async getExpenses() {
    const response = await api.get('/api/planned-transactions/expenses')
    return response.data
  },

  /**
   * Récupérer la projection mensuelle
   */
  async getProjection() {
    const response = await api.get('/api/planned-transactions/projection')
    return response.data
  },

  /**
   * Récupérer les statistiques
   */
  async getStats() {
    const response = await api.get('/api/planned-transactions/stats')
    return response.data
  },

  /**
   * Créer une nouvelle transaction planifiée
   */
  async create(data) {
    const response = await api.post('/api/planned-transactions', data)
    return response.data
  },

  /**
   * Mettre à jour une transaction planifiée
   */
  async update(id, data) {
    const response = await api.put(`/api/planned-transactions/${id}`, data)
    return response.data
  },

  /**
   * Supprimer une transaction planifiée
   */
  async delete(id) {
    const response = await api.delete(`/api/planned-transactions/${id}`)
    return response.data
  },

  /**
   * Activer/Désactiver une transaction planifiée
   */
  async toggle(id) {
    const response = await api.post(`/api/planned-transactions/${id}/toggle`)
    return response.data
  },

  /**
   * Exécuter une transaction planifiée
   */
  async execute(id) {
    const response = await api.post(`/api/planned-transactions/${id}/execute`)
    return response.data
  },

  /**
   * Exécuter toutes les transactions dues
   */
  async executeAll() {
    const response = await api.post('/api/planned-transactions/execute-all')
    return response.data
  }
}

export default plannedTransactionService
