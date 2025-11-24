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

// CRUD Transactions
$router->post('/api/transactions', 'TransactionController@create');
$router->get('/api/transactions', 'TransactionController@index');
$router->get('/api/transactions/{id}', 'TransactionController@show');
$router->put('/api/transactions/{id}', 'TransactionController@update');
$router->delete('/api/transactions/{id}', 'TransactionController@delete');

// Routes spécifiques transactions
$router->get('/api/transactions/recent', 'TransactionController@recent');
$router->get('/api/transactions/stats', 'TransactionController@stats');
$router->get('/api/transactions/total', 'TransactionController@total');