import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { 
  Card, CardContent, CardDescription, CardHeader, CardTitle,
  Button, Input, Label,
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/atoms'
import { 
  ArrowLeft, 
  Loader2, 
  PiggyBank, 
  CheckCircle2,
  CalendarDays,
  Target
} from 'lucide-react'
import budgetService from '@/services/budgetService'
import categoryService from '@/services/categoryService'

export default function CreateBudgetPage() {
  const navigate = useNavigate()
  const [categories, setCategories] = useState([])
  const [formData, setFormData] = useState({
    category_id: '',
    max_amount: '',
    start_date: new Date().toISOString().split('T')[0], // Aujourd'hui par défaut
    end_date: ''
  })
  const [loading, setLoading] = useState(false)
  const [loadingCategories, setLoadingCategories] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(false)

  // Charger les catégories de dépenses
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await categoryService.getExpenses()
        
        // La réponse est directement { success, message, data: { categories, count } }
        if (response.success && response.data?.categories) {
          setCategories(response.data.categories)
        } else if (response.data?.success && response.data?.data?.categories) {
          // Fallback si wrappé par Axios
          setCategories(response.data.data.categories)
        } else {
          console.error('Structure de réponse inattendue:', response)
          setError('Format de réponse invalide')
        }
      } catch (err) {
        console.error('Erreur chargement catégories:', err)
        setError('Impossible de charger les catégories')
      } finally {
        setLoadingCategories(false)
      }
    }
    fetchCategories()
  }, [])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    // Validation
    if (!formData.category_id) {
      setError('Veuillez sélectionner une catégorie')
      setLoading(false)
      return
    }

    if (!formData.max_amount || parseFloat(formData.max_amount) <= 0) {
      setError('Veuillez entrer un montant maximum valide')
      setLoading(false)
      return
    }

    try {
      await budgetService.create({
        category_id: parseInt(formData.category_id),
        max_amount: parseFloat(formData.max_amount),
        start_date: formData.start_date,
        end_date: formData.end_date || null
      })
      
      setSuccess(true)
      
      // Rediriger vers le dashboard après 1.5 secondes
      setTimeout(() => {
        window.location.href = '/dashboard'
      }, 1500)
    } catch (err) {
      console.error('Erreur création budget:', err)
      setError(err.response?.data?.message || err.message || 'Erreur lors de la création du budget')
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const selectedCategory = categories.find(c => c.id === parseInt(formData.category_id))

  if (success) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <Card className="w-full max-w-md text-center">
          <CardContent className="pt-8 pb-8">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle2 className="h-8 w-8 text-green-600" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Budget créé !</h2>
            <p className="text-gray-500 mb-4">
              Votre budget pour "{selectedCategory?.name}" a été créé avec succès.
            </p>
            <p className="text-sm text-gray-400">Redirection vers le dashboard...</p>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-2xl mx-auto px-4 py-4">
          <Button 
            variant="ghost" 
            onClick={() => navigate('/dashboard')}
            className="gap-2 text-gray-600 hover:text-gray-900 -ml-2"
          >
            <ArrowLeft className="h-4 w-4" />
            Retour au dashboard
          </Button>
        </div>
      </div>

      {/* Contenu principal */}
      <div className="max-w-2xl mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-2xl font-bold text-gray-900">Créer un budget</h1>
          <p className="text-gray-500 mt-1">Définissez un plafond de dépenses pour une catégorie</p>
        </div>

        <form onSubmit={handleSubmit}>
          {error && (
            <div className="mb-6 p-4 text-sm text-red-600 bg-red-50 rounded-lg border border-red-100">
              {error}
            </div>
          )}

          {/* Sélection de la catégorie */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <Target className="h-5 w-5 text-[#C38EF0]" />
                Catégorie de dépense
              </CardTitle>
              <CardDescription>Choisissez la catégorie à budgétiser</CardDescription>
            </CardHeader>
            <CardContent>
              {loadingCategories ? (
                <div className="flex items-center justify-center py-4">
                  <Loader2 className="h-6 w-6 animate-spin text-[#C38EF0]" />
                  <span className="ml-2 text-gray-500">Chargement des catégories...</span>
                </div>
              ) : (
                <Select
                  value={formData.category_id}
                  onValueChange={(value) => handleChange('category_id', value)}
                >
                  <SelectTrigger className="w-full">
                    <SelectValue placeholder="Sélectionnez une catégorie" />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((category) => (
                      <SelectItem key={category.id} value={category.id.toString()}>
                        {category.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              )}
            </CardContent>
          </Card>

          {/* Montant maximum */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <PiggyBank className="h-5 w-5 text-[#C38EF0]" />
                Montant maximum
              </CardTitle>
              <CardDescription>Définissez le plafond de dépenses mensuel</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="relative">
                <Input
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  value={formData.max_amount}
                  onChange={(e) => handleChange('max_amount', e.target.value)}
                  className="pl-4 pr-12 text-lg"
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">
                  €
                </span>
              </div>
              <p className="text-sm text-gray-500 mt-2">
                Vous serez alerté lorsque vos dépenses atteindront 80% de ce montant.
              </p>
            </CardContent>
          </Card>

          {/* Période du budget */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <CalendarDays className="h-5 w-5 text-[#C38EF0]" />
                Période du budget
              </CardTitle>
              <CardDescription>Définissez la durée de validité du budget</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <Label htmlFor="start_date" className="text-sm font-medium text-gray-700">
                  Date de début
                </Label>
                <Input
                  type="date"
                  id="start_date"
                  value={formData.start_date}
                  onChange={(e) => handleChange('start_date', e.target.value)}
                  className="mt-1"
                />
              </div>
              <div>
                <Label htmlFor="end_date" className="text-sm font-medium text-gray-700">
                  Date de fin (optionnel)
                </Label>
                <Input
                  type="date"
                  id="end_date"
                  value={formData.end_date}
                  onChange={(e) => handleChange('end_date', e.target.value)}
                  min={formData.start_date}
                  className="mt-1"
                />
                <p className="text-sm text-gray-500 mt-1">
                  Laissez vide pour un budget sans fin définie.
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Info sur le fonctionnement */}
          <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p className="text-sm text-blue-800">
              <strong>💡 Bon à savoir :</strong> Seules les nouvelles dépenses (transactions négatives) 
              effectuées après la création du budget seront comptabilisées. Les transactions passées 
              n'affecteront pas ce budget.
            </p>
          </div>

          {/* Bouton de soumission */}
          <div className="flex gap-3">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate('/dashboard')}
              className="flex-1"
            >
              Annuler
            </Button>
            <Button
              type="submit"
              disabled={loading || loadingCategories}
              className="flex-1 bg-[#C38EF0] hover:bg-[#B570E8] text-white"
            >
              {loading ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin mr-2" />
                  Création...
                </>
              ) : (
                'Créer le budget'
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
