import { Wallet, Plus, CreditCard, PiggyBank, Building2, Trash } from 'lucide-react'
import { Button } from '@/components/atoms'
import { Card, CardHeader, CardTitle, CardContent, ScrollArea } from '@/components/atoms'
import { cn } from '@/lib/utils'
import { formatCurrency } from '@/utils/format-utils'

// Icônes par type de compte
const accountTypeIcons = {
  checking: CreditCard,
  savings: PiggyBank,
  other: Building2
}

const accountTypeLabels = {
  checking: 'Courant',
  savings: 'Épargne',
  other: 'Autre'
}

/**
 * Liste compacte des comptes
 */
export function AccountsList({ accounts = [], onAddAccount, onSelectAccount, onDeleteAccount, className }) {
  const totalBalance = accounts.reduce((sum, acc) => {
    return sum + (parseFloat(acc.current_balance) || 0)
  }, 0)

  return (
    <Card className={cn("h-full flex flex-col", className)}>
      <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
        <div className="flex items-center gap-2">
          <Wallet className="h-4 w-4 text-[#C38EF0]" />
          <CardTitle className="text-sm font-medium">Mes comptes</CardTitle>
        </div>
        {onAddAccount && (
          <button 
            onClick={onAddAccount}
            className="p-1 rounded-md hover:bg-muted transition-colors"
            title="Ajouter un compte"
          >
            <Plus className="h-4 w-4 text-muted-foreground" />
          </button>
        )}
      </CardHeader>
      <CardContent className="flex-1 p-0">
        <ScrollArea className="h-full px-4 pb-4">
          {accounts.length > 0 ? (
            <div className="space-y-2">
              {accounts.map((account) => {
                const Icon = accountTypeIcons[account.type] || accountTypeIcons.other
                const balance = parseFloat(account.current_balance) || 0
                
                return (
                  <div
                    key={account.id}
                    className="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-muted/50 transition-colors"
                  >
                    <button
                      onClick={() => onSelectAccount?.(account)}
                      className="flex items-center gap-3 flex-1 text-left"
                      style={{ background: 'none', border: 'none', padding: 0 }}
                    >
                      <div className="p-2 rounded-lg bg-gradient-to-br from-[#C38EF0]/20 to-[#BCF08E]/20">
                        <Icon className="h-4 w-4 text-[#C38EF0]" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium truncate">{account.name}</p>
                        <p className="text-xs text-muted-foreground">
                          {accountTypeLabels[account.type] || 'Compte'}
                        </p>
                      </div>
                      <p className={cn(
                        "text-sm font-semibold shrink-0",
                        balance >= 0 ? 'text-foreground' : 'text-red-600'
                      )}>
                        {formatCurrency(balance)}
                      </p>
                    </button>
                    {onDeleteAccount && (
                      <Button
                        variant="ghost"
                        size="icon"
                        className="ml-1 text-red-500 hover:bg-red-100"
                        title="Supprimer le compte"
                        onClick={() => onDeleteAccount(account)}
                      >
                        <Trash className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                )
              })}
              
              {/* Total */}
              <div className="pt-2 mt-2 border-t">
                <div className="flex items-center justify-between px-2">
                  <span className="text-xs text-muted-foreground">Total</span>
                  <span className="text-sm font-bold">{formatCurrency(totalBalance)}</span>
                </div>
              </div>
            </div>
          ) : (
            <div className="h-full flex flex-col items-center justify-center text-muted-foreground text-sm py-8">
              <Wallet className="h-8 w-8 mb-2 opacity-50" />
              <p>Aucun compte</p>
              {onAddAccount && (
                <button 
                  onClick={onAddAccount}
                  className="mt-2 text-xs text-[#C38EF0] hover:underline"
                >
                  Créer un compte
                </button>
              )}
            </div>
          )}
        </ScrollArea>
      </CardContent>
    </Card>
  )
}

export default AccountsList
