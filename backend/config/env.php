<?php
/**
 * Configuration de l'environnement
 * 
 * Ce fichier définit les variables d'environnement pour l'application
 */

// Configuration JWT
$_ENV['JWT_SECRET'] = 'votre-cle-secrete-jwt-changez-la-en-production-' . md5('finance-flow');

// Configuration de l'application
$_ENV['APP_NAME'] = 'Finance Flow';
$_ENV['APP_ENV'] = 'development'; // development, staging, production
$_ENV['APP_DEBUG'] = true;
$_ENV['APP_URL'] = 'http://localhost';

// Configuration de la base de données (déjà définie dans config/database.php)
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'finance_flow';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';

// Configuration des emails (pour futures fonctionnalités)
$_ENV['MAIL_HOST'] = 'smtp.gmail.com';
$_ENV['MAIL_PORT'] = '587';
$_ENV['MAIL_USERNAME'] = '';
$_ENV['MAIL_PASSWORD'] = '';
$_ENV['MAIL_FROM_ADDRESS'] = 'no-reply@finance-flow.com';
$_ENV['MAIL_FROM_NAME'] = 'Finance Flow';

// Configuration des limites
$_ENV['RATE_LIMIT_REQUESTS'] = '60';
$_ENV['RATE_LIMIT_MINUTES'] = '1';

// Configuration des uploads
$_ENV['UPLOAD_MAX_SIZE'] = '2048'; // KB
$_ENV['ALLOWED_EXTENSIONS'] = 'jpg,jpeg,png,gif';

// Fuseau horaire
date_default_timezone_set('Europe/Paris');

// Configuration des erreurs selon l'environnement
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
}

// Créer le dossier de logs s'il n'existe pas
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}