import api from './api'

/**
 * Service de gestion des transactions
 * Suit le même pattern qu'AuthService
 */
class TransactionService {
  /**
   * Créer une nouvelle transaction
   * @param {Object} transactionData - Données de la transaction
   * @param {string} transactionData.title - Titre de la transaction
   * @param {string} transactionData.description - Description (optionnelle)
   * @param {number} transactionData.amount - Montant
   * @param {string} transactionData.date - Date (YYYY-MM-DD)
   * @param {string} [transactionData.location] - Lieu (optionnel)
   * @param {number} [transactionData.category_id] - ID de catégorie (optionnel)
   * @param {number} [transactionData.sub_category_id] - ID de sous-catégorie (optionnel)
   * @param {number} [transactionData.account_id] - ID de compte (optionnel)
   * @returns {Promise<Object>} Réponse de l'API
   */
  async createTransaction(transactionData) {
    try {
      const response = await api.post('/api/transactions', {
        title: transactionData.title,
        description: transactionData.description || null,
        amount: parseFloat(transactionData.amount),
        date: transactionData.date,
        location: transactionData.location || null,
        category_id: transactionData.category_id || null,
        sub_category_id: transactionData.sub_category_id || null,
        account_id: transactionData.account_id || null
      })

      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer toutes les transactions de l'utilisateur
   * @param {Object} params - Paramètres de requête
   * @param {number} [params.limit] - Limite du nombre de transactions
   * @param {number} [params.offset] - Décalage pour la pagination
   * @param {string} [params.search] - Terme de recherche
   * @param {string} [params.start_date] - Date de début (YYYY-MM-DD)
   * @param {string} [params.end_date] - Date de fin (YYYY-MM-DD)
   * @returns {Promise<Object>} Liste des transactions
   */
  async getTransactions(params = {}) {
    try {
      const queryString = new URLSearchParams(params).toString()
      const response = await api.get(`/api/transactions${queryString ? `?${queryString}` : ''}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer une transaction spécifique
   * @param {number} id - ID de la transaction
   * @returns {Promise<Object>} Données de la transaction
   */
  async getTransaction(id) {
    try {
      const response = await api.get(`/api/transactions/${id}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Mettre à jour une transaction
   * @param {number} id - ID de la transaction
   * @param {Object} transactionData - Nouvelles données de la transaction
   * @returns {Promise<Object>} Transaction mise à jour
   */
  async updateTransaction(id, transactionData) {
    try {
      const updateData = {}
      
      // Inclure seulement les champs qui ont une valeur
      if (transactionData.title !== undefined) updateData.title = transactionData.title
      if (transactionData.description !== undefined) updateData.description = transactionData.description || null
      if (transactionData.amount !== undefined) updateData.amount = parseFloat(transactionData.amount)
      if (transactionData.date !== undefined) updateData.date = transactionData.date
      if (transactionData.location !== undefined) updateData.location = transactionData.location || null
      if (transactionData.category_id !== undefined) updateData.category_id = transactionData.category_id || null
      if (transactionData.sub_category_id !== undefined) updateData.sub_category_id = transactionData.sub_category_id || null
      if (transactionData.account_id !== undefined) updateData.account_id = transactionData.account_id || null

      const response = await api.put(`/api/transactions/${id}`, updateData)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Supprimer une transaction
   * @param {number} id - ID de la transaction
   * @returns {Promise<Object>} Réponse de suppression
   */
  async deleteTransaction(id) {
    try {
      const response = await api.delete(`/api/transactions/${id}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les transactions récentes
   * @param {number} limit - Nombre de transactions à récupérer (défaut: 10)
   * @returns {Promise<Object>} Transactions récentes
   */
  async getRecentTransactions(limit = 10) {
    try {
      const response = await api.get(`/api/transactions/recent?limit=${limit}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les statistiques des transactions
   * @returns {Promise<Object>} Statistiques des transactions
   */
  async getTransactionStats() {
    try {
      const response = await api.get('/api/transactions/stats')
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Calculer le total des transactions
   * @param {string} [startDate] - Date de début (YYYY-MM-DD)
   * @param {string} [endDate] - Date de fin (YYYY-MM-DD)
   * @returns {Promise<Object>} Total des transactions
   */
  async getTotalAmount(startDate = null, endDate = null) {
    try {
      const params = {}
      if (startDate) params.start_date = startDate
      if (endDate) params.end_date = endDate
      
      const queryString = new URLSearchParams(params).toString()
      const response = await api.get(`/api/transactions/total${queryString ? `?${queryString}` : ''}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Rechercher des transactions
   * @param {string} searchTerm - Terme de recherche
   * @returns {Promise<Object>} Résultats de recherche
   */
  async searchTransactions(searchTerm) {
    try {
      const response = await api.get(`/api/transactions?search=${encodeURIComponent(searchTerm)}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  /**
   * Récupérer les transactions par période
   * @param {string} startDate - Date de début (YYYY-MM-DD)
   * @param {string} endDate - Date de fin (YYYY-MM-DD)
   * @returns {Promise<Object>} Transactions pour la période
   */
  async getTransactionsByDateRange(startDate, endDate) {
    try {
      const response = await api.get(`/api/transactions?start_date=${startDate}&end_date=${endDate}`)
      return response.data
    } catch (error) {
      throw this.handleError(error)
    }
  }

  // === Méthodes utilitaires ===

  /**
   * Formater le montant pour l'affichage
   * @param {number} amount - Montant à formater
   * @returns {string} Montant formaté
   */
  formatAmount(amount) {
    return new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR',
      minimumFractionDigits: 2
    }).format(amount)
  }

  /**
   * Formater la date pour l'affichage
   * @param {string} dateString - Date au format YYYY-MM-DD
   * @returns {string} Date formatée
   */
  formatDate(dateString) {
    const date = new Date(dateString)
    return new Intl.DateTimeFormat('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    }).format(date)
  }

  /**
   * Obtenir la date actuelle au format YYYY-MM-DD
   * @returns {string} Date actuelle
   */
  getCurrentDate() {
    return new Date().toISOString().split('T')[0]
  }

  /**
   * Valider les données de transaction côté client
   * @param {Object} transactionData - Données à valider
   * @returns {Object} Résultat de validation
   */
  validateTransactionData(transactionData) {
    const errors = {}

    // Validation du titre
    if (!transactionData.title?.trim()) {
      errors.title = 'Le titre est requis'
    } else if (transactionData.title.length > 150) {
      errors.title = 'Le titre ne peut pas dépasser 150 caractères'
    }

    // Validation du montant (peut être négatif pour les dépenses)
    if (transactionData.amount === undefined || transactionData.amount === null || transactionData.amount === '') {
      errors.amount = 'Le montant est requis'
    } else if (isNaN(parseFloat(transactionData.amount))) {
      errors.amount = 'Le montant doit être numérique'
    } else if (parseFloat(transactionData.amount) === 0) {
      errors.amount = 'Le montant ne peut pas être zéro'
    }

    // Validation de la date
    if (!transactionData.date) {
      errors.date = 'La date est requise'
    } else if (isNaN(Date.parse(transactionData.date))) {
      errors.date = 'Format de date invalide'
    }

    // Validation des champs optionnels
    if (transactionData.description && transactionData.description.length > 1000) {
      errors.description = 'La description ne peut pas dépasser 1000 caractères'
    }

    if (transactionData.location && transactionData.location.length > 100) {
      errors.location = 'Le lieu ne peut pas dépasser 100 caractères'
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  }

  /**
   * Gérer les erreurs de l'API (même pattern qu'AuthService)
   * @param {Error} error - Erreur axios
   * @returns {Error} Erreur formatée
   */
  handleError(error) {
    console.error('Erreur TransactionService:', error)

    if (error.response) {
      // Erreur de réponse API
      const { data, status } = error.response
      const message = data?.message || data?.error || `Erreur ${status}`
      
      const formattedError = new Error(message)
      formattedError.status = status
      formattedError.data = data
      return formattedError
    } else if (error.request) {
      // Erreur de réseau
      return new Error('Erreur de connexion au serveur')
    } else {
      // Autre erreur
      return error
    }
  }
}

// Instance unique du service
const transactionService = new TransactionService()

export default transactionService