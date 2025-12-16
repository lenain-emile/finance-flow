import { useNavigate } from 'react-router-dom'
import { Plus } from 'lucide-react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  ScrollArea,
  Progress,
  Badge,
  Button
} from '@/components/atoms'
import { formatCurrency, getProgressColor } from '@/utils/format-utils'

/**
 * Modal pour afficher tous les budgets en détail
 */
export function BudgetsModal({ 
  open, 
  onOpenChange, 
  budgets = [],
  onBudgetClick 
}) {
  const navigate = useNavigate()

  const getStatusBadge = (status, percentage) => {
    if (status === 'exceeded' || percentage >= 100) {
      return <Badge variant="destructive">Dépassé</Badge>
    }
    if (status === 'warning' || percentage >= 80) {
      return <Badge variant="warning">Attention</Badge>
    }
    return <Badge variant="success">OK</Badge>
  }

  // Calculer les totaux
  const totals = budgets.reduce((acc, budget) => {
    acc.maxAmount += budget.max_amount || 0
    acc.spentAmount += budget.spent_amount || 0
    return acc
  }, { maxAmount: 0, spentAmount: 0 })

  const totalPercentage = totals.maxAmount > 0 
    ? (totals.spentAmount / totals.maxAmount) * 100 
    : 0

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
        <DialogHeader className="flex flex-row items-center justify-between">
          <DialogTitle>Gestion des budgets</DialogTitle>
          <Button
            size="sm"
            onClick={() => {
              onOpenChange(false)
              navigate('/budgets/create')
            }}
            className="bg-[#C38EF0] hover:bg-[#B570E8] text-white"
          >
            <Plus className="h-4 w-4 mr-1" />
            Nouveau budget
          </Button>
        </DialogHeader>

        {/* Résumé global */}
        <div className="p-4 bg-muted/50 rounded-lg">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium">Budget global</span>
            <span className="text-sm text-muted-foreground">
              {formatCurrency(totals.spentAmount)} / {formatCurrency(totals.maxAmount)}
            </span>
          </div>
          <Progress 
            value={Math.min(totalPercentage, 100)} 
            className="h-3"
            indicatorClassName={getProgressColor(totalPercentage)}
          />
          <p className="text-xs text-muted-foreground mt-1 text-right">
            {Math.round(totalPercentage)}% utilisé
          </p>
        </div>

        {/* Liste des budgets */}
        <ScrollArea className="flex-1 -mx-6 px-6">
          <div className="space-y-4 py-2">
            {budgets.length > 0 ? (
              budgets.map((budget) => {
                const percentage = budget.percentage || 
                  (budget.spent_amount / budget.max_amount) * 100 || 0
                const cappedPercentage = Math.min(percentage, 100)
                const remaining = budget.max_amount - (budget.spent_amount || 0)

                return (
                  <button
                    key={budget.id}
                    onClick={() => onBudgetClick?.(budget)}
                    className="w-full p-4 rounded-lg border hover:border-[#C38EF0]/50 transition-colors text-left"
                  >
                    <div className="flex items-center justify-between mb-3">
                      <div>
                        <h4 className="font-medium">
                          {budget.category_name && budget.category_name.trim() !== '' ? budget.category_name : `Budget ${budget.id}`}
                        </h4>
                        <p className="text-sm text-muted-foreground">
                          Restant: {formatCurrency(remaining)}
                        </p>
                      </div>
                      {getStatusBadge(budget.status, percentage)}
                    </div>
                    
                    <Progress 
                      value={cappedPercentage} 
                      className="h-2 mb-2"
                      indicatorClassName={getProgressColor(percentage, budget.status)}
                    />
                    
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span>Dépensé: {formatCurrency(budget.spent_amount || 0)}</span>
                      <span>Maximum: {formatCurrency(budget.max_amount)}</span>
                    </div>
                  </button>
                )
              })
            ) : (
              <div className="py-8 text-center text-muted-foreground flex flex-col items-center gap-3">
                <span>Aucun budget défini</span>
                <Button
                  onClick={() => {
                    onOpenChange(false)
                    navigate('/budgets/create')
                  }}
                  className="bg-[#C38EF0] hover:bg-[#B570E8] text-white"
                >
                  <Plus className="h-4 w-4 mr-1" />
                  Créer votre premier budget
                </Button>
              </div>
            )}
          </div>
        </ScrollArea>
      </DialogContent>
    </Dialog>
  )
}

export default BudgetsModal
