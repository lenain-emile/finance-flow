/**
 * Utilitaires pour les budgets
 */

/**
 * Formate un montant en devise EUR
 */
export const formatCurrency = (value) => {
  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 0
  }).format(value)
}

/**
 * Retourne la classe CSS pour la couleur de progression
 */
export const getProgressColor = (percentage, status) => {
  if (status === 'exceeded' || percentage >= 100) return 'bg-red-500'
  if (status === 'warning' || percentage >= 80) return 'bg-orange-500'
  return 'bg-green-500'
}

/**
 * Retourne le libellé du statut
 */
export const getStatusLabel = (status, percentage) => {
  if (status === 'exceeded') return 'Dépassé'
  if (status === 'warning') return 'Attention'
  return `${Math.round(percentage)}%`
}
