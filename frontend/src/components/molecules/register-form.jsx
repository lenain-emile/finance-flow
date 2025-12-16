import { Link } from "react-router-dom"
import { Button } from "@/components/atoms"
import { InputWithError } from "@/components/molecules"
import { useFormValidation } from "@/utils/form-validation"

export function RegisterForm({ onSubmit, isLoading = false, className = "" }) {
  const {
    formData,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateForm,
    isFormValid
  } = useFormValidation({
    name: "",
    email: "",
    password: ""
  })

  // Soumission du formulaire
  const handleSubmit = (e) => {
    e.preventDefault()
    
    if (validateForm()) {
      onSubmit?.(formData)
    }
  }

  return (
    <form onSubmit={handleSubmit} className={`form-container ${className}`}>
      
      {/* Champ Nom */}
      <InputWithError
        id="name"
        name="name"
        label="Nom ou pseudo"
        placeholder="Entrez votre nom ou pseudo"
        value={formData.name}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.name}
        touched={touched.name}
        disabled={isLoading}
        required
      />

      {/* Champ Email */}
      <InputWithError
        id="email"
        name="email"
        type="email"
        label="Email"
        placeholder="exemple@email.com"
        value={formData.email}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.email}
        touched={touched.email}
        disabled={isLoading}
        required
      />

      {/* Champ Mot de passe */}
      <InputWithError
        id="password"
        name="password"
        type="password"
        label="Mot de passe"
        placeholder="Minimum 8 caractères"
        value={formData.password}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.password}
        touched={touched.password}
        disabled={isLoading}
        required
      />

      {/* Bouton de soumission */}
      <Button 
        type="submit" 
        className="w-full bg-gradient-to-r from-[#C38EF0] to-[#BCF08E] hover:from-[#B570E8] hover:to-[#A8E67A] text-white shadow-lg transition-all duration-200"
        disabled={!isFormValid || isLoading}
      >
        {isLoading ? "Création en cours..." : "Créer un compte"}
      </Button>

      {/* Informations supplémentaires */}
      <div className="text-center">
        <p className="text-sm text-gray-600">
          Déjà un compte ? {" "}
          <Link to="/login" className="text-[#C38EF0] hover:text-[#B570E8] font-medium hover:underline transition-colors duration-200">
            Se connecter
          </Link>
        </p>
      </div>

    </form>
  )
}