import { Button } from "../atoms/button"
import { InputWithError } from "./input-with-error"
import { useFormValidation } from "../../utils/form-validation"

export function TransactionForm({ onSubmit, isLoading = false, className = "" }) {
  const {
    formData,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateForm,
    isFormValid
  } = useFormValidation({
    title: "",
    description: "",
    amount: "",
    date: new Date().toISOString().split('T')[0], // Date actuelle par défaut
    location: "",
    category_id: "",
    sub_category_id: "",
    account_id: ""
  })

  // Soumission du formulaire
  const handleSubmit = (e) => {
    e.preventDefault()
    
    if (validateForm()) {
      onSubmit?.(formData)
    }
  }

  return (
    <form onSubmit={handleSubmit} className={`space-y-6 ${className}`}>
      
      {/* Champ Titre */}
      <InputWithError
        id="title"
        name="title"
        type="text"
        label="Titre de la transaction"
        placeholder="Ex: Courses alimentaires"
        value={formData.title}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.title}
        touched={touched.title}
        disabled={isLoading}
        required
      />

      {/* Champ Montant */}
      <InputWithError
        id="amount"
        name="amount"
        type="number"
        step="0.01"
        label="Montant (€)"
        placeholder="Ex: 25.50"
        value={formData.amount}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.amount}
        touched={touched.amount}
        disabled={isLoading}
        required
      />

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

        {/* Compte (optionnel) */}
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
            disabled={isLoading}
          >
            <option value="">Sélectionner un compte</option>
            <option value="1">Compte courant</option>
            <option value="2">Compte épargne</option>
            <option value="3">Carte de crédit</option>
            <option value="4">Espèces</option>
          </select>
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