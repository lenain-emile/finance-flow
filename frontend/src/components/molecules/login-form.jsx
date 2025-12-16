import { Link } from "react-router-dom"
import { Button } from "@/components/atoms"
import { InputWithError } from "@/components/molecules"
import { useFormValidation } from "@/utils/form-validation"

export function LoginForm({ onSubmit, isLoading = false, className = "" }) {
  const {
    formData,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateForm,
    isFormValid
  } = useFormValidation({
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
    <form onSubmit={handleSubmit} className={`space-y-6 ${className}`}>
      
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
        placeholder="Entrez votre mot de passe"
        value={formData.password}
        onChange={handleChange}
        onBlur={handleBlur}
        error={errors.password}
        touched={touched.password}
        disabled={isLoading}
        required
      />

      {/* Lien mot de passe oublié */}
      <div className="flex items-center justify-between">
        <div className="text-sm">
          <Link to="/forgot-password" className="text-[#C38EF0] hover:text-[#B570E8] font-medium hover:underline transition-colors duration-200">
            Mot de passe oublié ?
          </Link>
        </div>
      </div>

      {/* Bouton de soumission */}
      <Button 
        type="submit" 
        className="w-full bg-gradient-to-r from-[#C38EF0] to-[#BCF08E] hover:from-[#B570E8] hover:to-[#A8E67A] text-white shadow-lg transition-all duration-200"
        disabled={!isFormValid || isLoading}
      >
        {isLoading ? "Connexion en cours..." : "Se connecter"}
      </Button>

      {/* Informations supplémentaires */}
      <div className="text-center">
        <p className="text-sm text-gray-600">
          Pas encore de compte ? {" "}
          <Link to="/register" className="text-[#C38EF0] hover:text-[#B570E8] font-medium hover:underline transition-colors duration-200">
            Créer un compte
          </Link>
        </p>
      </div>

    </form>
  )
}

export default LoginForm