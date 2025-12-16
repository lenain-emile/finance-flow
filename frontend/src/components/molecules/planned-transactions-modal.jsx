import { Calendar, Play, Clock, AlertCircle } from 'lucide-react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  ScrollArea,
  Badge,
  Button
} from '@/components/atoms'
import { cn } from '@/lib/utils'

/**
 * Modal pour les transactions planifiées (dues et à venir)
 */
export function PlannedTransactionsModal({ 
  open, 
  onOpenChange, 
  dueTransactions = [],
  upcomingTransactions = [],
  onExecute,
  onExecuteAll
}) {
  const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { 
      day: '2-digit', 
      month: 'short'
    })
  }

  const formatAmount = (amount, operationType) => {
    const num = Math.abs(parseFloat(amount))
    const formatted = new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(num)
    return operationType === 'income' ? `+${formatted}` : `-${formatted}`
  }

  const getFrequencyLabel = (frequency) => {
    const labels = {
      daily: 'Quotidien',
      weekly: 'Hebdomadaire',
      monthly: 'Mensuel',
      yearly: 'Annuel'
    }
    return labels[frequency] || frequency
  }

  const TransactionItem = ({ transaction, isDue = false }) => (
    <div className={cn(
      "flex items-center gap-4 p-3 rounded-lg border",
      isDue ? "border-red-200 bg-red-50" : "border-muted"
    )}>
      <div className={cn(
        "p-2 rounded-lg shrink-0",
        transaction.operation_type === 'income' 
          ? "bg-green-100 text-green-600" 
          : "bg-red-100 text-red-600"
      )}>
        {isDue ? <AlertCircle className="h-4 w-4" /> : <Clock className="h-4 w-4" />}
      </div>
      
      <div className="flex-1 min-w-0">
        <p className="font-medium truncate">{transaction.title}</p>
        <div className="flex items-center gap-2 text-xs text-muted-foreground">
          <Calendar className="h-3 w-3" />
          <span>{formatDate(transaction.next_date)}</span>
          <Badge variant="outline" className="text-xs">
            {getFrequencyLabel(transaction.frequency)}
          </Badge>
        </div>
      </div>
      
      <div className="text-right shrink-0">
        <p className={cn(
          "font-semibold",
          transaction.operation_type === 'income' ? "text-green-600" : "text-red-600"
        )}>
          {formatAmount(transaction.amount, transaction.operation_type)}
        </p>
        {isDue && onExecute && (
          <Button 
            size="sm" 
            variant="ghost"
            className="h-7 mt-1 text-xs"
            onClick={() => onExecute(transaction.id)}
          >
            <Play className="h-3 w-3 mr-1" />
            Exécuter
          </Button>
        )}
      </div>
    </div>
  )

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Transactions planifiées</DialogTitle>
        </DialogHeader>

        <ScrollArea className="flex-1 -mx-6 px-6">
          {/* Transactions dues */}
          {dueTransactions.length > 0 && (
            <div className="mb-6">
              <div className="flex items-center justify-between mb-3">
                <h3 className="font-medium flex items-center gap-2 text-red-600">
                  <AlertCircle className="h-4 w-4" />
                  À exécuter ({dueTransactions.length})
                </h3>
                {onExecuteAll && dueTransactions.length > 1 && (
                  <Button size="sm" variant="destructive" onClick={onExecuteAll}>
                    <Play className="h-3 w-3 mr-1" />
                    Tout exécuter
                  </Button>
                )}
              </div>
              <div className="space-y-2">
                {dueTransactions.map((t) => (
                  <TransactionItem key={t.id} transaction={t} isDue />
                ))}
              </div>
            </div>
          )}

          {/* Transactions à venir */}
          <div>
            <h3 className="font-medium flex items-center gap-2 mb-3">
              <Calendar className="h-4 w-4" />
              À venir ({upcomingTransactions.length})
            </h3>
            {upcomingTransactions.length > 0 ? (
              <div className="space-y-2">
                {upcomingTransactions.map((t) => (
                  <TransactionItem key={t.id} transaction={t} />
                ))}
              </div>
            ) : (
              <div className="py-8 text-center text-muted-foreground">
                Aucune transaction planifiée à venir
              </div>
            )}
          </div>
        </ScrollArea>
      </DialogContent>
    </Dialog>
  )
}

export default PlannedTransactionsModal
