import { useState, useEffect } from "react"
import { Button } from "@/components/atoms"
import { InputWithError } from "@/components/molecules"
import { useFormValidation } from "@/utils/form-validation"
import { TrendingDown, TrendingUp } from "lucide-react"
import accountService from "@/services/accountService"

export function TransactionForm({ onSubmit, isLoading = false, className = "" }) {
  const [accounts, setAccounts] = useState([])
  const [accountsLoading, setAccountsLoading] = useState(true)

  // Charger les comptes de l'utilisateur au montage
  useEffect(() => {
    const loadAccounts = async () => {
      try {
        const response = await accountService.getAll()
        setAccounts(response.data?.accounts || response.accounts || [])
      } catch (error) {
        console.error('Erreur chargement comptes:', error)
      } finally {
        setAccountsLoading(false)
      }
    }
    loadAccounts()
  }, [])

  const {
    formData,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateForm,
    isFormValid,
    setFormData
  } = useFormValidation({
    title: "",
    description: "",
    amount: "",
    transactionType: "expense", // "expense" (dépense) ou "income" (revenu)
    date: new Date().toISOString().split('T')[0], // Date actuelle par défaut
    location: "",
    category_id: "",
    sub_category_id: "",
    account_id: ""
  })

  // Gestion du changement de type de transaction
  const handleTypeChange = (type) => {
    setFormData(prev => ({ ...prev, transactionType: type }))
  }

  // Soumission du formulaire
  const handleSubmit = (e) => {
    e.preventDefault()
    
    if (validateForm()) {
      // Convertir le montant en négatif si c'est une dépense
      const finalAmount = formData.transactionType === 'expense' 
        ? -Math.abs(parseFloat(formData.amount))
        : Math.abs(parseFloat(formData.amount))
      
      onSubmit?.({
        ...formData,
        amount: finalAmount
      })
    }
  }

  return (
    <form onSubmit={handleSubmit} className={`space-y-6 ${className}`}>
      
      {/* Sélecteur Type de transaction (Dépense / Revenu) */}
      <div className="form-field">
        <label className="form-label mb-3">Type de transaction</label>
        <div className="grid grid-cols-2 gap-3">
          <button
            type="button"
            onClick={() => handleTypeChange('expense')}
            className={`flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 ${
              formData.transactionType === 'expense'
                ? 'border-red-500 bg-red-50 text-red-700 shadow-md'
                : 'border-gray-200 bg-white text-gray-600 hover:border-red-300 hover:bg-red-50/50'
            }`}
          >
            <TrendingDown className={`h-5 w-5 ${formData.transactionType === 'expense' ? 'text-red-500' : 'text-gray-400'}`} />
            <span className="font-medium">Dépense</span>
          </button>
          <button
            type="button"
            onClick={() => handleTypeChange('income')}
            className={`flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 ${
              formData.transactionType === 'income'
                ? 'border-green-500 bg-green-50 text-green-700 shadow-md'
                : 'border-gray-200 bg-white text-gray-600 hover:border-green-300 hover:bg-green-50/50'
            }`}
          >
            <TrendingUp className={`h-5 w-5 ${formData.transactionType === 'income' ? 'text-green-500' : 'text-gray-400'}`} />
            <span className="font-medium">Revenu</span>
          </button>
        </div>
      </div>

      {/* Champ Titre */}
      <InputWithError
        id="title"
        name="title"
        type="text"
        label="Titre de la transaction"
        placeholder={formData.transactionType === 'expense' ? "Ex: Courses alimentaires" : "Ex: Salaire mensuel"}
        value={formData.title}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.title}
        touched={touched.title}
        disabled={isLoading}
        required
      />

      {/* Champ Montant */}
      <div className="form-field">
        <label htmlFor="amount" className="form-label">
          Montant (€)
        </label>
        <div className="relative">
          <div className={`absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg ${
            formData.transactionType === 'expense' ? 'text-red-500' : 'text-green-500'
          }`}>
            {formData.transactionType === 'expense' ? '-' : '+'}
          </div>
          <input
            id="amount"
            name="amount"
            type="number"
            step="0.01"
            min="0"
            placeholder="0.00"
            value={formData.amount}
            onChange={handleChange}
            onBlur={handleBlur}
            disabled={isLoading}
            required
            className={`form-input pl-8 text-lg font-semibold ${
              formData.transactionType === 'expense' 
                ? 'focus:border-red-500 focus:ring-red-500/20' 
                : 'focus:border-green-500 focus:ring-green-500/20'
            }`}
          />
          <div className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
            €
          </div>
        </div>
        {errors.amount && touched.amount && (
          <p className="form-error">{errors.amount}</p>
        )}
      </div>

      {/* Champ Date */}
      <InputWithError
        id="date"
        name="date"
        type="date"
        label="Date"
        value={formData.date}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.date}
        touched={touched.date}
        disabled={isLoading}
        required
      />

      {/* Champ Description (optionnel) */}
      <div className="form-field">
        <label htmlFor="description" className="form-label">
          Description (optionnelle)
        </label>
        <textarea
          id="description"
          name="description"
          rows={3}
          className="form-input"
          placeholder="Détails sur la transaction..."
          value={formData.description}
          onChange={handleChange}
          onBlur={handleBlur}
          disabled={isLoading}
          maxLength={1000}
        />
        {errors.description && touched.description && (
          <p className="form-error">{errors.description}</p>
        )}
        <p className="text-sm text-gray-500 mt-1">
          {formData.description.length}/1000 caractères
        </p>
      </div>

      {/* Champ Lieu (optionnel) */}
      <InputWithError
        id="location"
        name="location"
        type="text"
        label="Lieu (optionnel)"
        placeholder="Ex: Carrefour, Restaurant"
        value={formData.location}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.location}
        touched={touched.location}
        disabled={isLoading}
      />

      {/* Section optionnelle - Catégorisation */}
      <div className="border-t pt-6">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Catégorisation (optionnelle)</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {/* Catégorie */}
          <div className="form-field">
            <label htmlFor="category_id" className="form-label">
              Catégorie
            </label>
            <select
              id="category_id"
              name="category_id"
              className="form-input"
              value={formData.category_id}
              onChange={handleChange}
              disabled={isLoading}
            >
              <option value="">Sélectionner une catégorie</option>
              <option value="1">Alimentation</option>
              <option value="2">Transport</option>
              <option value="3">Logement</option>
              <option value="4">Loisirs</option>
              <option value="5">Santé</option>
              <option value="6">Vêtements</option>
              <option value="7">Éducation</option>
              <option value="8">Autres</option>
            </select>
          </div>

          {/* Sous-catégorie */}
          <div className="form-field">
            <label htmlFor="sub_category_id" className="form-label">
              Sous-catégorie
            </label>
            <select
              id="sub_category_id"
              name="sub_category_id"
              className="form-input"
              value={formData.sub_category_id}
              onChange={handleChange}
              disabled={isLoading}
            >
              <option value="">Sélectionner une sous-catégorie</option>
              {/* Les options seront dynamiques selon la catégorie sélectionnée */}
              <option value="1">Courses</option>
              <option value="2">Restaurant</option>
              <option value="3">Snacks</option>
            </select>
          </div>
        </div>

        {/* Compte */}
        <div className="form-field mt-4">
          <label htmlFor="account_id" className="form-label">
            Compte
          </label>
          <select
            id="account_id"
            name="account_id"
            className="form-input"
            value={formData.account_id}
            onChange={handleChange}
            disabled={isLoading || accountsLoading}
            required
          >
            <option value="">
              {accountsLoading ? 'Chargement...' : 'Sélectionner un compte'}
            </option>
            {accounts.map((account) => (
              <option key={account.id} value={account.id}>
                {account.name} ({new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(account.current_balance || account.initial_balance || 0)})
              </option>
            ))}
          </select>
          {accounts.length === 0 && !accountsLoading && (
            <p className="text-sm text-amber-600 mt-1">
              Aucun compte trouvé. Créez d'abord un compte.
            </p>
          )}
        </div>
      </div>

      {/* Bouton de soumission */}
      <Button 
        type="submit" 
        className="w-full bg-gradient-to-r from-[#C38EF0] to-[#BCF08E] hover:from-[#B570E8] hover:to-[#A8E67A] text-white shadow-lg transition-all duration-200"
        disabled={!isFormValid || isLoading}
      >
        {isLoading ? "Ajout en cours..." : "Ajouter la transaction"}
      </Button>

      {/* Informations supplémentaires */}
      <div className="text-center">
        <p className="text-sm text-gray-600">
          💡 Astuce : Plus vous renseignez de détails, meilleur sera le suivi de vos finances
        </p>
      </div>

    </form>
  )
}

export default TransactionForm