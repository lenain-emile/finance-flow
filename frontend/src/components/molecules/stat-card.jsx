import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { Card } from '@/components/atoms'
import { cn } from '@/lib/utils'

/**
 * Carte statistique compacte pour le dashboard
 */
export function StatCard({ 
  title, 
  value, 
  icon: Icon, 
  trend = null, 
  trendValue = null,
  variant = 'default',
  className 
}) {
  const formatValue = (val) => {
    if (typeof val === 'number') {
      return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(val)
    }
    return val
  }

  const getTrendIcon = () => {
    if (trend === 'up') return <TrendingUp className="h-3 w-3" />
    if (trend === 'down') return <TrendingDown className="h-3 w-3" />
    return <Minus className="h-3 w-3" />
  }

  const getTrendColor = () => {
    if (variant === 'income') return trend === 'up' ? 'text-green-500' : 'text-red-500'
    if (variant === 'expense') return trend === 'up' ? 'text-red-500' : 'text-green-500'
    return trend === 'up' ? 'text-green-500' : trend === 'down' ? 'text-red-500' : 'text-gray-500'
  }

  const getValueColor = () => {
    if (variant === 'income') return 'text-green-600'
    if (variant === 'expense') return 'text-red-600'
    return 'text-foreground'
  }

  const getIconBgColor = () => {
    if (variant === 'income') return 'bg-green-100 text-green-600'
    if (variant === 'expense') return 'bg-red-100 text-red-600'
    return 'bg-gradient-to-br from-[#C38EF0]/20 to-[#BCF08E]/20 text-[#C38EF0]'
  }

  return (
    <Card className={cn("p-4", className)}>
      <div className="flex items-center justify-between">
        <div className="flex-1 min-w-0">
          <p className="text-xs font-medium text-muted-foreground truncate">
            {title}
          </p>
          <p className={cn("text-xl font-bold mt-1 truncate", getValueColor())}>
            {formatValue(value)}
          </p>
          {(trend || trendValue) && (
            <div className={cn("flex items-center gap-1 mt-1 text-xs", getTrendColor())}>
              {getTrendIcon()}
              <span>{trendValue}</span>
            </div>
          )}
        </div>
        {Icon && (
          <div className={cn("p-2 rounded-lg shrink-0", getIconBgColor())}>
            <Icon className="h-5 w-5" />
          </div>
        )}
      </div>
    </Card>
  )
}

export default StatCard
