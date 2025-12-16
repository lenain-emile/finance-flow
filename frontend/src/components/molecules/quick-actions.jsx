import { Plus } from 'lucide-react'
import { cn } from '@/lib/utils'

/**
 * Boutons d'actions rapides
 */
export function QuickActions({ 
  onAddTransaction, 
  className 
}) {
  const actions = [
    {
      icon: Plus,
      label: 'Nouvelle transaction',
      onClick: onAddTransaction,
      primary: true
    },
    
  ]

  return (
    <div className={cn("flex items-center gap-2", className)}>
      {actions.map((action, index) => (
        <button
          key={index}
          onClick={action.onClick}
          title={action.label}
          className={cn(
            "p-2 rounded-lg transition-all",
            action.primary 
              ? "bg-gradient-to-r from-[#C38EF0] to-[#BCF08E] text-white hover:opacity-90 shadow-md"
              : "bg-muted hover:bg-muted/80 text-muted-foreground"
          )}
        >
          <action.icon className="h-5 w-5" />
        </button>
      ))}
    </div>
  )
}

export default QuickActions
