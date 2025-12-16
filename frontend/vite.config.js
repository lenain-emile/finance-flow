import path from "path"
import tailwindcss from "@tailwindcss/vite"
import react from "@vitejs/plugin-react"
import { defineConfig } from "vite"
 
// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/finance-flow/backend/public',
        changeOrigin: true,
        secure: false,
      }
    }
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks(id) {
          // Séparer les librairies React
          if (id.includes('node_modules/react') || id.includes('node_modules/react-dom')) {
            return 'vendor-react'
          }
          // Séparer React Router
          if (id.includes('node_modules/react-router')) {
            return 'vendor-router'
          }
          // Séparer Recharts (librairie de graphiques)
          if (id.includes('node_modules/recharts')) {
            return 'vendor-charts'
          }
          // Séparer Axios
          if (id.includes('node_modules/axios')) {
            return 'vendor-axios'
          }
          // Séparer les icônes Lucide
          if (id.includes('node_modules/lucide-react')) {
            return 'vendor-icons'
          }
        }
      }
    },
    // Augmenter légèrement la limite pour éviter les warnings inutiles
    chunkSizeWarningLimit: 600
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV || 'development')
  }
})
