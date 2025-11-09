# Finance Flow 💰

A personal finance manager built with React + Vite + Bootstrap for the frontend and PHP (MVC architecture) with PHPUnit for the backend.

## 📁 Project Structure

```
finance-flow/
├── frontend/          # React + Vite + Bootstrap application
├── backend/           # PHP MVC application with PHPUnit tests
├── database/          # SQL schema and sample data
├── .gitignore
└── README.md
```

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ and npm
- PHP 8+
- MariaDB/MySQL
- Composer

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/finance-flow.git
   cd finance-flow
   ```

2. **Setup Frontend**
   ```bash
   cd frontend
   npm install
   npm run dev
   ```

3. **Setup Backend**
   ```bash
   cd backend
   composer install
   php -S localhost:8000 -t public
   ```

4. **Setup Database**
   ```bash
   # Import database schema
   mysql -u root -p < database/schema.sql
   ```

## 🌿 Git Branching Strategy

- `main` → Production-ready code
- `dev` → Development branch (base for all features)
- `feature/*` → Feature branches

### Feature Branches:
- `feature/add-transaction` → Add new transaction form
- `feature/qualify-transaction` → Add date, place, title, optional description
- `feature/categories` → Manage categories and subcategories
- `feature/list-transactions` → Display the list of all transactions
- `feature/balance` → Display remaining balance
- `feature/sort-filter` → Sort and filter transactions
- `feature/charts` → Add graphs with Chart.js

## 📊 Features

- ✅ Add new transactions
- ✅ Qualify transactions (date, place, title, description)
- ✅ Categorize transactions with categories/subcategories
- ✅ Display all transactions
- ✅ Display current balance
- ✅ Filter and sort transactions
- ✅ Visualize data with charts

## 🛠️ Tech Stack

**Frontend:**
- React 18
- Vite
- Bootstrap 5
- Chart.js
- Axios

**Backend:**
- PHP 8+
- MVC Architecture
- PHPUnit
- MariaDB/MySQL

## 📱 Responsive Design

Mobile-first responsive design ensuring optimal experience across all devices.

## 🧪 Testing

```bash
# Backend tests
cd backend
composer test

# Frontend tests
cd frontend
npm test
```

## 🚀 Deployment

Project ready for deployment on Plesk hosting platform.

## 📄 License

This project is licensed under the MIT License.