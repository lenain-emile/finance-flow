import { useState } from 'react'
import { AreaChart, Area, XAxis, YAxis, Tooltip, ResponsiveContainer } from 'recharts'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/atoms'
import { cn } from '@/lib/utils'
import { formatCurrency } from '@/utils/format-utils'

/**
 * Graphique d'évolution du solde
 */
export function BalanceChart({ data = [], className }) {
  const [period, setPeriod] = useState('30')

  const periods = [
    { value: '7', label: '7j' },
    { value: '30', label: '30j' },
    { value: '90', label: '90j' }
  ]

  // Filtrer les données selon la période
  const filteredData = data.slice(-parseInt(period))

  const CustomTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
      return (
        <div className="bg-white p-3 rounded-lg shadow-lg border">
          <p className="text-sm text-muted-foreground">{label}</p>
          <p className="text-lg font-bold text-[#C38EF0]">
            {formatCurrency(payload[0].value)}
          </p>
        </div>
      )
    }
    return null
  }

  return (
    <Card className={cn("h-full flex flex-col", className)}>
      <CardHeader className="pb-2 flex-row items-center justify-between space-y-0">
        <CardTitle className="text-sm font-medium">Évolution du solde</CardTitle>
        <div className="flex gap-1">
          {periods.map((p) => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={cn(
                "px-2 py-1 text-xs rounded-md transition-colors",
                period === p.value
                  ? "bg-[#C38EF0] text-white"
                  : "bg-muted text-muted-foreground hover:bg-muted/80"
              )}
            >
              {p.label}
            </button>
          ))}
        </div>
      </CardHeader>
      <CardContent className="flex-1 pb-2 min-h-[200px]">
        {filteredData.length > 0 ? (
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={filteredData} margin={{ top: 5, right: 5, left: -20, bottom: 0 }}>
              <defs>
                <linearGradient id="colorSolde" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#C38EF0" stopOpacity={0.3} />
                  <stop offset="95%" stopColor="#BCF08E" stopOpacity={0.1} />
                </linearGradient>
              </defs>
              <XAxis 
                dataKey="dateFormatted" 
                tick={{ fontSize: 10 }}
                tickLine={false}
                axisLine={false}
                interval="preserveStartEnd"
              />
              <YAxis 
                tick={{ fontSize: 10 }}
                tickLine={false}
                axisLine={false}
                tickFormatter={(value) => `${(value / 1000).toFixed(0)}k`}
              />
              <Tooltip content={<CustomTooltip />} />
              <Area
                type="monotone"
                dataKey="solde"
                stroke="#C38EF0"
                strokeWidth={2}
                fill="url(#colorSolde)"
              />
            </AreaChart>
          </ResponsiveContainer>
        ) : (
          <div className="h-full flex items-center justify-center text-muted-foreground text-sm">
            Aucune donnée disponible
          </div>
        )}
      </CardContent>
    </Card>
  )
}

export default BalanceChart
