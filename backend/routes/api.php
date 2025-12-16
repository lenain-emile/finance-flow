<?php
/**
 * Fichier de définition des routes API
 * 
 * Ce fichier sera utilisé pour définir toutes les routes de l'API.
 * Il sera chargé automatiquement par le routeur dans index.php
 * 
 * @var FinanceFlow\Core\Router $router
 */

// ================================
// ROUTES UTILISATEUR
// ================================

// Authentification
$router->post('/api/users/register', 'UserController@register');
$router->post('/api/users/login', 'UserController@login');
$router->post('/api/users/logout', 'UserController@logout');
$router->post('/api/users/refresh-token', 'UserController@refreshToken');

// Profil utilisateur
$router->get('/api/users/me', 'UserController@getProfile');
$router->put('/api/users/me', 'UserController@updateProfile');
$router->delete('/api/users/me', 'UserController@deleteAccount');

// Vérification d'email
$router->post('/api/users/verify-email', 'UserController@verifyEmail');

// Vérification de disponibilité
$router->get('/api/users/check-username/{username}', 'UserController@checkUsernameAvailability');
$router->get('/api/users/check-email/{email}', 'UserController@checkEmailAvailability');

// ================================
// ROUTES FUTURES
// ================================

// Les routes spécifiques aux autres fonctionnalités seront ajoutées ici
// au fur et à mesure du développement des branches feature/*

// ================================
// ROUTES TRANSACTIONS
// ================================

// Routes spécifiques transactions (AVANT les routes avec {id})
$router->get('/api/transactions/recent', 'TransactionController@recent');
$router->get('/api/transactions/stats', 'TransactionController@stats');
$router->get('/api/transactions/total', 'TransactionController@total');

// CRUD Transactions
$router->post('/api/transactions', 'TransactionController@create');
$router->get('/api/transactions', 'TransactionController@index');
$router->get('/api/transactions/{id}', 'TransactionController@show');
$router->put('/api/transactions/{id}', 'TransactionController@update');
$router->delete('/api/transactions/{id}', 'TransactionController@delete');

// ================================
// ROUTES BUDGETS
// ================================

// Routes spécifiques budgets (AVANT les routes avec {id})
$router->get('/api/budgets/alerts', 'BudgetController@alerts');
$router->get('/api/budgets/exceeded', 'BudgetController@exceeded');
$router->get('/api/budgets/stats', 'BudgetController@stats');
$router->post('/api/budgets/check-impact', 'BudgetController@checkImpact');

// CRUD Budgets
$router->post('/api/budgets', 'BudgetController@create');
$router->get('/api/budgets', 'BudgetController@index');
$router->get('/api/budgets/{id}', 'BudgetController@show');
$router->put('/api/budgets/{id}', 'BudgetController@update');
$router->delete('/api/budgets/{id}', 'BudgetController@delete');

// ================================
// ROUTES COMPTES
// ================================

// Routes spécifiques comptes (AVANT les routes avec {id})
// Note: /api/accounts/{id}/balance contient {id} donc doit rester après les routes sans paramètre

// CRUD Comptes
$router->post('/api/accounts', 'AccountController@create');
$router->get('/api/accounts', 'AccountController@index');
$router->get('/api/accounts/{id}', 'AccountController@show');
$router->get('/api/accounts/{id}/balance', 'AccountController@balance');
$router->put('/api/accounts/{id}', 'AccountController@update');
$router->delete('/api/accounts/{id}', 'AccountController@delete');

// ================================
// ROUTES TRANSACTIONS PLANIFIÉES
// ================================

// Routes spécifiques (AVANT les routes avec {id})
$router->get('/api/planned-transactions/due', 'PlannedTransactionController@due');
$router->get('/api/planned-transactions/upcoming', 'PlannedTransactionController@upcoming');
$router->get('/api/planned-transactions/incomes', 'PlannedTransactionController@incomes');
$router->get('/api/planned-transactions/expenses', 'PlannedTransactionController@expenses');
$router->get('/api/planned-transactions/projection', 'PlannedTransactionController@projection');
$router->get('/api/planned-transactions/stats', 'PlannedTransactionController@stats');
$router->post('/api/planned-transactions/execute-all', 'PlannedTransactionController@executeAll');

// CRUD Transactions planifiées
$router->post('/api/planned-transactions', 'PlannedTransactionController@create');
$router->get('/api/planned-transactions', 'PlannedTransactionController@index');
$router->get('/api/planned-transactions/{id}', 'PlannedTransactionController@show');
$router->put('/api/planned-transactions/{id}', 'PlannedTransactionController@update');
$router->delete('/api/planned-transactions/{id}', 'PlannedTransactionController@delete');

// Actions sur une transaction planifiée
$router->post('/api/planned-transactions/{id}/toggle', 'PlannedTransactionController@toggle');
$router->post('/api/planned-transactions/{id}/execute', 'PlannedTransactionController@execute');

// ================================
// ROUTES CATÉGORIES
// ================================

$router->get('/api/categories/expenses', 'CategoryController@expenses');
$router->get('/api/categories/incomes', 'CategoryController@incomes');