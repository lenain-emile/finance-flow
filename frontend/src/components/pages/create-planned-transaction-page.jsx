import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { 
  Card, CardContent, CardDescription, CardHeader, CardTitle,
  Button, Input, Label,
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/atoms'
import { Navbar } from '@/components/molecules'
import { 
  ArrowLeft, 
  Loader2, 
  CalendarClock, 
  CheckCircle2,
  TrendingDown,
  TrendingUp,
  Calendar,
  Repeat,
  Wallet,
  Tag,
  FileText
} from 'lucide-react'
import plannedTransactionService from '@/services/plannedTransactionService'
import categoryService from '@/services/categoryService'
import accountService from '@/services/accountService'

// Constantes pour les fréquences
const FREQUENCIES = [
  { value: 'daily', label: 'Quotidien' },
  { value: 'weekly', label: 'Hebdomadaire' },
  { value: 'monthly', label: 'Mensuel' },
  { value: 'yearly', label: 'Annuel' }
]

// Constantes pour les unités de durée
const DURATION_UNITS = [
  { value: 'day', label: 'Jour(s)' },
  { value: 'month', label: 'Mois' },
  { value: 'year', label: 'Année(s)' }
]

export default function CreatePlannedTransactionPage() {
  const navigate = useNavigate()
  
  // États pour les données
  const [expenseCategories, setExpenseCategories] = useState([])
  const [incomeCategories, setIncomeCategories] = useState([])
  const [accounts, setAccounts] = useState([])
  
  // État du formulaire
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    amount: '',
    operation_type: 'expense', // 'expense' ou 'income'
    frequency: 'monthly',
    next_date: new Date().toISOString().split('T')[0],
    category_id: '',
    account_id: '',
    interest_rate: '',
    duration: '',
    duration_unit: '',
    active: true
  })
  
  // États de chargement et feedback
  const [loading, setLoading] = useState(false)
  const [loadingData, setLoadingData] = useState(true)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(false)

  // Charger les données au montage
  useEffect(() => {
    const fetchData = async () => {
      try {
        // Charger en parallèle catégories et comptes
        const [expensesRes, incomesRes, accountsRes] = await Promise.all([
          categoryService.getExpenses(),
          categoryService.getIncomes(),
          accountService.getAll()
        ])
        
        // Traiter les catégories de dépenses
        if (expensesRes.success && expensesRes.data?.categories) {
          setExpenseCategories(expensesRes.data.categories)
        } else if (expensesRes.data?.success && expensesRes.data?.data?.categories) {
          setExpenseCategories(expensesRes.data.data.categories)
        }
        
        // Traiter les catégories de revenus
        if (incomesRes.success && incomesRes.data?.categories) {
          setIncomeCategories(incomesRes.data.categories)
        } else if (incomesRes.data?.success && incomesRes.data?.data?.categories) {
          setIncomeCategories(incomesRes.data.data.categories)
        }
        
        // Traiter les comptes
        if (accountsRes.success && accountsRes.data?.accounts) {
          setAccounts(accountsRes.data.accounts)
        } else if (accountsRes.data?.accounts) {
          setAccounts(accountsRes.data.accounts)
        } else if (Array.isArray(accountsRes.accounts)) {
          setAccounts(accountsRes.accounts)
        }
        
      } catch (err) {
        console.error('Erreur chargement données:', err)
        setError('Impossible de charger les données nécessaires')
      } finally {
        setLoadingData(false)
      }
    }
    
    fetchData()
  }, [])

  // Catégories à afficher selon le type d'opération
  const currentCategories = formData.operation_type === 'expense' 
    ? expenseCategories 
    : incomeCategories

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    // Validation
    if (!formData.title.trim()) {
      setError('Veuillez entrer un titre')
      setLoading(false)
      return
    }

    if (!formData.amount || parseFloat(formData.amount) <= 0) {
      setError('Veuillez entrer un montant valide')
      setLoading(false)
      return
    }

    if (!formData.next_date) {
      setError('Veuillez sélectionner une date de début')
      setLoading(false)
      return
    }

    try {
      // Préparer les données
      const dataToSend = {
        title: formData.title.trim(),
        amount: parseFloat(formData.amount),
        operation_type: formData.operation_type,
        frequency: formData.frequency,
        next_date: formData.next_date,
        active: formData.active
      }

      // Ajouter les champs optionnels s'ils sont remplis
      if (formData.description?.trim()) {
        dataToSend.description = formData.description.trim()
      }
      if (formData.category_id) {
        dataToSend.category_id = parseInt(formData.category_id)
      }
      if (formData.account_id) {
        dataToSend.account_id = parseInt(formData.account_id)
      }
      if (formData.interest_rate) {
        dataToSend.interest_rate = parseFloat(formData.interest_rate)
      }
      if (formData.duration) {
        dataToSend.duration = parseInt(formData.duration)
      }
      if (formData.duration_unit) {
        dataToSend.duration_unit = formData.duration_unit
      }

      await plannedTransactionService.create(dataToSend)
      
      setSuccess(true)
      
      // Rediriger vers le dashboard après 1.5 secondes
      // Utiliser navigate avec state pour forcer le rafraîchissement
      setTimeout(() => {
        navigate('/dashboard', { replace: true, state: { refresh: true } })
      }, 1500)
    } catch (err) {
      console.error('Erreur création transaction planifiée:', err)
      setError(err.response?.data?.message || err.message || 'Erreur lors de la création')
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (field, value) => {
    setFormData(prev => {
      const newData = { ...prev, [field]: value }
      
      // Réinitialiser la catégorie si on change le type d'opération
      if (field === 'operation_type') {
        newData.category_id = ''
      }
      
      return newData
    })
  }

  // Écran de succès
  if (success) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <Card className="w-full max-w-md text-center">
          <CardContent className="pt-8 pb-8">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle2 className="h-8 w-8 text-green-600" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Transaction planifiée créée !</h2>
            <p className="text-gray-500 mb-4">
              "{formData.title}" a été ajoutée avec succès.
            </p>
            <p className="text-sm text-gray-400">Redirection vers le dashboard...</p>
          </CardContent>
        </Card>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <Navbar currentPage="planned-transactions" />
      
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
          <div className="flex items-center gap-3 mb-2">
            <div className="p-2 bg-purple-100 rounded-lg">
              <CalendarClock className="h-6 w-6 text-[#C38EF0]" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900">Transaction planifiée</h1>
          </div>
          <p className="text-gray-500 mt-1">Planifiez une transaction récurrente</p>
        </div>

        {loadingData ? (
          <Card>
            <CardContent className="flex items-center justify-center py-12">
              <Loader2 className="h-8 w-8 animate-spin text-[#C38EF0]" />
              <span className="ml-3 text-gray-500">Chargement des données...</span>
            </CardContent>
          </Card>
        ) : (
          <form onSubmit={handleSubmit}>
            {error && (
              <div className="mb-6 p-4 text-sm text-red-600 bg-red-50 rounded-lg border border-red-100">
                {error}
              </div>
            )}

            {/* Type d'opération */}
            <Card className="mb-6">
              <CardHeader className="pb-4">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                  <Tag className="h-4 w-4 text-[#C38EF0]" />
                  Type d'opération
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    onClick={() => handleChange('operation_type', 'expense')}
                    className={`flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 ${
                      formData.operation_type === 'expense'
                        ? 'border-red-500 bg-red-50 text-red-700 shadow-md'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-red-300 hover:bg-red-50/50'
                    }`}
                  >
                    <TrendingDown className={`h-5 w-5 ${formData.operation_type === 'expense' ? 'text-red-500' : 'text-gray-400'}`} />
                    <span className="font-medium">Dépense</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleChange('operation_type', 'income')}
                    className={`flex items-center justify-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 ${
                      formData.operation_type === 'income'
                        ? 'border-green-500 bg-green-50 text-green-700 shadow-md'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-green-300 hover:bg-green-50/50'
                    }`}
                  >
                    <TrendingUp className={`h-5 w-5 ${formData.operation_type === 'income' ? 'text-green-500' : 'text-gray-400'}`} />
                    <span className="font-medium">Revenu</span>
                  </button>
                </div>
              </CardContent>
            </Card>

            {/* Informations principales */}
            <Card className="mb-6">
              <CardHeader className="pb-4">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                  <FileText className="h-4 w-4 text-[#C38EF0]" />
                  Informations principales
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Titre */}
                <div className="space-y-2">
                  <Label htmlFor="title">Titre *</Label>
                  <Input
                    id="title"
                    placeholder={formData.operation_type === 'expense' ? "Ex: Loyer, Abonnement Netflix..." : "Ex: Salaire, Loyer perçu..."}
                    value={formData.title}
                    onChange={(e) => handleChange('title', e.target.value)}
                    disabled={loading}
                    maxLength={150}
                  />
                </div>

                {/* Montant */}
                <div className="space-y-2">
                  <Label htmlFor="amount">Montant (€) *</Label>
                  <div className="relative">
                    <div className={`absolute left-3 top-1/2 -translate-y-1/2 font-bold text-lg ${
                      formData.operation_type === 'expense' ? 'text-red-500' : 'text-green-500'
                    }`}>
                      {formData.operation_type === 'expense' ? '-' : '+'}
                    </div>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      className="pl-8"
                      value={formData.amount}
                      onChange={(e) => handleChange('amount', e.target.value)}
                      disabled={loading}
                    />
                  </div>
                </div>

                {/* Description */}
                <div className="space-y-2">
                  <Label htmlFor="description">Description (optionnelle)</Label>
                  <textarea
                    id="description"
                    className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="Détails supplémentaires..."
                    value={formData.description}
                    onChange={(e) => handleChange('description', e.target.value)}
                    disabled={loading}
                    maxLength={1000}
                  />
                </div>
              </CardContent>
            </Card>

            {/* Planification */}
            <Card className="mb-6">
              <CardHeader className="pb-4">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                  <Repeat className="h-4 w-4 text-[#C38EF0]" />
                  Planification
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Fréquence */}
                <div className="space-y-2">
                  <Label>Fréquence *</Label>
                  <Select
                    value={formData.frequency}
                    onValueChange={(value) => handleChange('frequency', value)}
                    disabled={loading}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner la fréquence" />
                    </SelectTrigger>
                    <SelectContent>
                      {FREQUENCIES.map((freq) => (
                        <SelectItem key={freq.value} value={freq.value}>
                          {freq.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Date de début */}
                <div className="space-y-2">
                  <Label htmlFor="next_date">Prochaine occurrence *</Label>
                  <div className="relative">
                    <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Input
                      id="next_date"
                      type="date"
                      className="pl-10"
                      value={formData.next_date}
                      onChange={(e) => handleChange('next_date', e.target.value)}
                      disabled={loading}
                    />
                  </div>
                </div>

                {/* Durée (optionnelle) */}
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="duration">Durée (optionnelle)</Label>
                    <Input
                      id="duration"
                      type="number"
                      min="1"
                      placeholder="Ex: 12"
                      value={formData.duration}
                      onChange={(e) => handleChange('duration', e.target.value)}
                      disabled={loading}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label>Unité</Label>
                    <Select
                      value={formData.duration_unit}
                      onValueChange={(value) => handleChange('duration_unit', value)}
                      disabled={loading || !formData.duration}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Unité" />
                      </SelectTrigger>
                      <SelectContent>
                        {DURATION_UNITS.map((unit) => (
                          <SelectItem key={unit.value} value={unit.value}>
                            {unit.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Catégorisation */}
            <Card className="mb-6">
              <CardHeader className="pb-4">
                <CardTitle className="text-base font-medium flex items-center gap-2">
                  <Wallet className="h-4 w-4 text-[#C38EF0]" />
                  Catégorisation
                </CardTitle>
                <CardDescription>
                  Associez cette transaction à une catégorie et un compte
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {/* Catégorie */}
                <div className="space-y-2">
                  <Label>Catégorie</Label>
                  <Select
                    value={formData.category_id}
                    onValueChange={(value) => handleChange('category_id', value)}
                    disabled={loading}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner une catégorie" />
                    </SelectTrigger>
                    <SelectContent>
                      {currentCategories.map((cat) => (
                        <SelectItem key={cat.id} value={String(cat.id)}>
                          {cat.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Compte */}
                <div className="space-y-2">
                  <Label>Compte associé</Label>
                  <Select
                    value={formData.account_id}
                    onValueChange={(value) => handleChange('account_id', value)}
                    disabled={loading}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Sélectionner un compte" />
                    </SelectTrigger>
                    <SelectContent>
                      {accounts.map((account) => (
                        <SelectItem key={account.id} value={String(account.id)}>
                          {account.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {/* Taux d'intérêt (pour les crédits/emprunts) */}
                {formData.operation_type === 'expense' && (
                  <div className="space-y-2">
                    <Label htmlFor="interest_rate">Taux d'intérêt % (optionnel)</Label>
                    <Input
                      id="interest_rate"
                      type="number"
                      step="0.01"
                      min="0"
                      max="100"
                      placeholder="Ex: 2.5"
                      value={formData.interest_rate}
                      onChange={(e) => handleChange('interest_rate', e.target.value)}
                      disabled={loading}
                    />
                    <p className="text-xs text-gray-500">Pour les crédits immobiliers, auto, etc.</p>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Boutons d'action */}
            <div className="flex gap-4">
              <Button
                type="button"
                variant="outline"
                className="flex-1"
                onClick={() => navigate('/dashboard')}
                disabled={loading}
              >
                Annuler
              </Button>
              <Button
                type="submit"
                className="flex-1 bg-[#C38EF0] hover:bg-[#B570E8] text-white"
                disabled={loading}
              >
                {loading ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Création...
                  </>
                ) : (
                  <>
                    <CalendarClock className="mr-2 h-4 w-4" />
                    Créer la transaction
                  </>
                )}
              </Button>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}
