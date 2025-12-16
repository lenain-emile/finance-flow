import { X, Search, Filter } from 'lucide-react'
import { useState } from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  ScrollArea,
  Input
} from '@/components/atoms'
import { cn } from '@/lib/utils'

/**
 * Modal pour afficher toutes les transactions
 */
export function TransactionsModal({ 
  open, 
  onOpenChange, 
  transactions = [],
  onTransactionClick 
}) {
  const [search, setSearch] = useState('')

  const filteredTransactions = transactions.filter(t => 
    t.title?.toLowerCase().includes(search.toLowerCase()) ||
    t.description?.toLowerCase().includes(search.toLowerCase())
  )

  const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { 
      day: '2-digit', 
      month: 'short',
      year: 'numeric'
    })
  }

  const formatAmount = (amount) => {
    const num = parseFloat(amount)
    const formatted = new Intl.NumberFormat('fr-FR', {
      style: 'currency',
      currency: 'EUR'
    }).format(Math.abs(num))
    return num >= 0 ? `+${formatted}` : `-${formatted}`
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Toutes les transactions</DialogTitle>
        </DialogHeader>
        
        {/* Barre de recherche */}
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Rechercher une transaction..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="pl-9"
          />
        </div>

        {/* Liste des transactions */}
        <ScrollArea className="flex-1 -mx-6 px-6">
          <div className="space-y-2 py-2">
            {filteredTransactions.length > 0 ? (
              filteredTransactions.map((transaction) => (
                <button
                  key={transaction.id}
                  onClick={() => onTransactionClick?.(transaction)}
                  className="w-full flex items-center gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors text-left"
                >
                  <div className={cn(
                    "w-2 h-2 rounded-full shrink-0",
                    parseFloat(transaction.amount) >= 0 ? "bg-green-500" : "bg-red-500"
                  )} />
                  <div className="flex-1 min-w-0">
                    <p className="font-medium truncate">{transaction.title}</p>
                    {transaction.description && (
                      <p className="text-sm text-muted-foreground truncate">
                        {transaction.description}
                      </p>
                    )}
                  </div>
                  <div className="text-right shrink-0">
                    <p className={cn(
                      "font-semibold",
                      parseFloat(transaction.amount) >= 0 ? "text-green-600" : "text-red-600"
                    )}>
                      {formatAmount(transaction.amount)}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {formatDate(transaction.date)}
                    </p>
                  </div>
                </button>
              ))
            ) : (
              <div className="py-8 text-center text-muted-foreground">
                {search ? 'Aucune transaction trouvée' : 'Aucune transaction'}
              </div>
            )}
          </div>
        </ScrollArea>
      </DialogContent>
    </Dialog>
  )
}

export default TransactionsModal
