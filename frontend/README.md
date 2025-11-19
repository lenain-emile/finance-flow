# Finance Flow - Frontend

Interface utilisateur moderne pour l'application de gestion financière Finance Flow.

## 🚀 Technologies

- **React 19** - Framework frontend moderne
- **Vite** - Build tool rapide avec HMR
- **Tailwind CSS** - Framework CSS utilitaire
- **shadcn/ui** - Composants UI élégants et accessibles
- **Axios** - Client HTTP pour les appels API
- **React Router** - Navigation côté client
- **Lucide React** - Icônes modernes

## 📁 Structure du Projet

```
src/
├── components/
│   ├── atoms/        # Composants de base (button, input, etc.)
│   ├── molecules/    # Composants composés (forms, navbar, etc.)
│   └── organisms/    # Composants complexes (pages sections)
├── contexts/         # Contextes React (AuthContext)
├── hooks/           # Hooks personnalisés
├── services/        # Services API et logique métier
├── styles/          # Styles CSS personnalisés
└── utils/           # Utilitaires et helpers
```

## 🔑 Fonctionnalités

- ✅ **Authentification JWT** - Inscription et connexion sécurisées
- ✅ **Interface responsive** - Design adaptatif pour tous les écrans
- ✅ **Validation de formulaires** - Validation côté client en temps réel
- ✅ **Gestion d'état** - Context API pour l'authentification
- ✅ **Composants réutilisables** - Architecture atomique

## 🛠️ Installation et Développement

```bash
# Installation des dépendances
npm install

# Lancement en mode développement
npm run dev

# Build pour la production
npm run build

# Prévisualisation du build
npm run preview

# Linting du code
npm run lint
```

## 🔧 Configuration

### Variables d'environnement
Le frontend est configuré pour communiquer avec le backend PHP via :
- **API Base URL** : `http://localhost/finance-flow/backend/public`
- **CORS** : Configuré pour `http://localhost:5173` (dev Vite)

### Proxy de développement
Vite est configuré avec un proxy pour les appels API :
```javascript
'/api': {
  target: 'http://localhost/finance-flow/backend/public',
  changeOrigin: true
}
```

## 🎨 Design System

Le projet utilise une approche de design atomique avec :
- **Atomes** : Composants de base (Button, Input, Label)
- **Molécules** : Combinaisons d'atomes (LoginForm, Navbar)
- **Organismes** : Sections complètes de page

## 📱 Responsive Design

- **Mobile First** - Design optimisé pour mobile d'abord
- **Breakpoints Tailwind** - sm, md, lg, xl, 2xl
- **Navigation adaptative** - Menu hamburger sur mobile

## 🔒 Sécurité

- **JWT Tokens** - Authentification stateless
- **Validation côté client** - Prévention des erreurs utilisateur
- **CORS configuré** - Protection contre les requêtes cross-origin malveillantes

## 🚀 Déploiement

```bash
# Build optimisé pour la production
npm run build

# Le dossier dist/ contient les fichiers prêts pour le déploiement
```

## 📈 Performance

- **Lazy Loading** - Chargement à la demande des composants
- **Tree Shaking** - Élimination du code inutilisé
- **Optimisation Vite** - Build ultra-rapide
- **CSS optimisé** - Tailwind CSS purge automatique
