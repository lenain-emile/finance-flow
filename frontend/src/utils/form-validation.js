import { useState } from "react"

// Règles de validation centralisées
export const validationRules = {
  name: {
    required: "Le nom est requis",
    minLength: { value: 2, message: "Le nom doit contenir au moins 2 caractères" }
  },
  email: {
    required: "L'email est requis",
    pattern: { 
      value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, 
      message: "Format d'email invalide" 
    }
  },
  password: {
    required: "Le mot de passe est requis",
    minLength: { value: 8, message: "Le mot de passe doit contenir au moins 8 caractères" }
  }
}

// Fonction de validation générique
export const validateField = (fieldName, value, rules = validationRules) => {
  const fieldRules = rules[fieldName]
  if (!fieldRules) return null

  // Validation required
  if (fieldRules.required && !value?.trim()) {
    return fieldRules.required
  }

  // Validation minLength
  if (fieldRules.minLength && value && value.length < fieldRules.minLength.value) {
    return fieldRules.minLength.message
  }

  // Validation pattern (regex)
  if (fieldRules.pattern && value && !fieldRules.pattern.value.test(value)) {
    return fieldRules.pattern.message
  }

  return null
}

// Hook personnalisé pour la gestion de formulaire
export const useFormValidation = (initialData) => {
  const [formData, setFormData] = useState(initialData)
  const [errors, setErrors] = useState({})
  const [touched, setTouched] = useState({})

  const validateFieldAndUpdate = (name, value) => {
    const error = validateField(name, value)
    setErrors(prev => ({
      ...prev,
      [name]: error
    }))
    return !error
  }

  const handleChange = (e) => {
    const { name, value } = e.target
    setFormData(prev => ({ ...prev, [name]: value }))
    
    if (touched[name]) {
      validateFieldAndUpdate(name, value)
    }
  }

  const handleBlur = (e) => {
    const { name, value } = e.target
    setTouched(prev => ({ ...prev, [name]: true }))
    validateFieldAndUpdate(name, value)
  }

  const validateForm = () => {
    const fields = Object.keys(formData)
    let isValid = true
    const newErrors = {}
    const newTouched = {}

    fields.forEach(field => {
      const error = validateField(field, formData[field])
      if (error) {
        newErrors[field] = error
        isValid = false
      }
      newTouched[field] = true
    })

    setErrors(newErrors)
    setTouched(newTouched)
    return isValid
  }

  const isFormValid = Object.keys(errors).filter(key => errors[key]).length === 0 &&
                     Object.keys(formData).every(key => formData[key]?.trim())

  return {
    formData,
    errors,
    touched,
    handleChange,
    handleBlur,
    validateForm,
    isFormValid,
    setFormData
  }
}