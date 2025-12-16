import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { 
  Card, CardContent, CardDescription, CardHeader, CardTitle,
  Button, Input, Label,
  Select, SelectContent, SelectItem, SelectTrigger, SelectValue
} from '@/components/atoms'
import { 
  ArrowLeft, 
  Loader2, 
  Building2, 
  PiggyBank, 
  CheckCircle2
} from 'lucide-react'
import accountService from '@/services/accountService'

const ACCOUNT_TYPES = [
  { value: 'checking', label: 'Compte Courant', icon: Building2, description: 'Pour vos opérations quotidiennes' },
  { value: 'savings', label: 'Compte Épargne', icon: PiggyBank, description: 'Pour mettre de l\'argent de côté' }
]

export default function CreateAccountPage() {
  const navigate = useNavigate()
  const [formData, setFormData] = useState({
    name: '',
    type: '',
    initial_balance: '',
    currency: 'EUR'
  })
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState(null)
  const [success, setSuccess] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    setLoading(true)
    setError(null)

    try {
      await accountService.create({
        name: formData.name,
        type: formData.type,
        initial_balance: parseFloat(formData.initial_balance) || 0,
        currency: formData.currency
      })
      
      setSuccess(true)
      
      // Rediriger vers le dashboard après 1.5 secondes
      // Utiliser window.location pour forcer un rechargement complet
      // et éviter les conflits React/Recharts
      setTimeout(() => {
        window.location.href = '/dashboard'
      }, 1500)
    } catch (err) {
      console.error('Erreur création compte:', err)
      setError(err.response?.data?.message || err.message || 'Erreur lors de la création du compte')
    } finally {
      setLoading(false)
    }
  }

  const handleChange = (field, value) => {
    setFormData(prev => ({ ...prev, [field]: value }))
  }

  const selectedType = ACCOUNT_TYPES.find(t => t.value === formData.type)

  if (success) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <Card className="w-full max-w-md text-center">
          <CardContent className="pt-8 pb-8">
            <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <CheckCircle2 className="h-8 w-8 text-green-600" />
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">Compte créé !</h2>
            <p className="text-gray-500 mb-4">Votre compte "{formData.name}" a été créé avec succès.</p>
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
          <h1 className="text-2xl font-bold text-gray-900">Créer un compte</h1>
          <p className="text-gray-500 mt-1">Ajoutez un nouveau compte pour suivre vos finances</p>
        </div>

        <form onSubmit={handleSubmit}>
          {error && (
            <div className="mb-6 p-4 text-sm text-red-600 bg-red-50 rounded-lg border border-red-100">
              {error}
            </div>
          )}

          {/* Choix du type de compte */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg">Type de compte</CardTitle>
              <CardDescription>Sélectionnez le type de compte que vous souhaitez créer</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {ACCOUNT_TYPES.map((type) => {
                  const Icon = type.icon
                  const isSelected = formData.type === type.value
                  return (
                    <button
                      key={type.value}
                      type="button"
                      onClick={() => handleChange('type', type.value)}
                      className={`p-4 rounded-xl border-2 text-left transition-all ${
                        isSelected 
                          ? 'border-[#C38EF0] bg-[#C38EF0]/5' 
                          : 'border-gray-200 hover:border-gray-300 bg-white'
                      }`}
                    >
                      <div className="flex items-start gap-3">
                        <div className={`p-2 rounded-lg ${
                          isSelected ? 'bg-[#C38EF0]/20' : 'bg-gray-100'
                        }`}>
                          <Icon className={`h-5 w-5 ${
                            isSelected ? 'text-[#C38EF0]' : 'text-gray-500'
                          }`} />
                        </div>
                        <div>
                          <p className={`font-medium ${
                            isSelected ? 'text-[#C38EF0]' : 'text-gray-900'
                          }`}>
                            {type.label}
                          </p>
                          <p className="text-xs text-gray-500 mt-0.5">{type.description}</p>
                        </div>
                      </div>
                    </button>
                  )
                })}
              </div>
            </CardContent>
          </Card>

          {/* Informations du compte */}
          <Card className="mb-6">
            <CardHeader>
              <CardTitle className="text-lg">Informations du compte</CardTitle>
              <CardDescription>Renseignez les détails de votre compte</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Nom du compte *</Label>
                <Input
                  id="name"
                  placeholder={selectedType ? `Ex: ${selectedType.label} - Ma Banque` : 'Ex: Compte Courant BNP'}
                  value={formData.name}
                  onChange={(e) => handleChange('name', e.target.value)}
                  required
                  minLength={3}
                  maxLength={100}
                  className="h-11"
                />
                <p className="text-xs text-gray-500">Entre 3 et 100 caractères</p>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="initial_balance">Solde initial</Label>
                  <div className="relative">
                    <Input
                      id="initial_balance"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                      value={formData.initial_balance}
                      onChange={(e) => handleChange('initial_balance', e.target.value)}
                      className="h-11 pr-14"
                    />
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">
                      EUR
                    </span>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="currency">Devise</Label>
                  <Select
                    value={formData.currency}
                    onValueChange={(value) => handleChange('currency', value)}
                  >
                    <SelectTrigger className="h-11">
                      <SelectValue placeholder="Devise" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="EUR">EUR - Euro</SelectItem>
                      <SelectItem value="USD">USD - Dollar</SelectItem>
                      <SelectItem value="GBP">GBP - Livre</SelectItem>
                      <SelectItem value="CHF">CHF - Franc Suisse</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Boutons d'action */}
          <div className="flex items-center justify-between gap-4">
            <Button
              type="button"
              variant="outline"
              onClick={() => navigate('/dashboard')}
              disabled={loading}
              className="h-11"
            >
              Annuler
            </Button>
            <Button
              type="submit"
              disabled={loading || !formData.name || !formData.type}
              className="h-11 px-8 bg-gradient-to-r from-[#C38EF0] to-[#BCF08E] text-white hover:opacity-90"
            >
              {loading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Création en cours...
                </>
              ) : (
                'Créer le compte'
              )}
            </Button>
          </div>
        </form>
      </div>
    </div>
  )
}
