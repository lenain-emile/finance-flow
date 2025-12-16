import api from './api'

/**
 * Service pour la gestion des comptes
 */
const accountService = {
  /**
   * Récupérer tous les comptes de l'utilisateur avec leurs soldes
   */
  async getAll() {
    const response = await api.get('/api/accounts')
    return response.data
  },

  /**
   * Récupérer un compte spécifique
   */
  async getById(id) {
    const response = await api.get(`/api/accounts/${id}`)
    return response.data
  },

  /**
   * Récupérer le solde d'un compte
   */
  async getBalance(id) {
    const response = await api.get(`/api/accounts/${id}/balance`)
    return response.data
  },

  /**
   * Créer un nouveau compte
   */
  async create(data) {
    const response = await api.post('/api/accounts', data)
    return response.data
  },

  /**
   * Mettre à jour un compte
   */
  async update(id, data) {
    const response = await api.put(`/api/accounts/${id}`, data)
    return response.data
  },

  /**
   * Supprimer un compte
   */
  async delete(id) {
    const response = await api.delete(`/api/accounts/${id}`)
    return response.data
  }
}

export default accountService
