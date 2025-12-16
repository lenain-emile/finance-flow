<?php

namespace FinanceFlow\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthService
{
    private string $secretKey;
    private string $algorithm;
    private int $tokenExpiry;
    private int $refreshExpiry;

    public function __construct()
    {
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-this-in-production';
        $this->algorithm = 'HS256';
        $this->tokenExpiry = 3600; // 1 heure
        $this->refreshExpiry = 2592000; // 30 jours
    }

    /**
     * Générer un token JWT
     */
    public function generateToken(int $userId, string $email, string $username): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->tokenExpiry;

        $payload = [
            'iss' => 'finance-flow-api', // Issuer
            'aud' => 'finance-flow-client', // Audience
            'iat' => $issuedAt, // Issued at
            'exp' => $expiration, // Expiration
            'user_id' => $userId,
            'email' => $email,
            'username' => $username,
            'type' => 'access_token'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Générer un token de rafraîchissement
     */
    public function generateRefreshToken(int $userId): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + $this->refreshExpiry;

        $payload = [
            'iss' => 'finance-flow-api',
            'aud' => 'finance-flow-client',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'user_id' => $userId,
            'type' => 'refresh_token'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Vérifier et décoder un token JWT
     */
    public function verifyToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // Vérifier le type de token
            if (!isset($decoded->type) || $decoded->type !== 'access_token') {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log("Erreur vérification token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier un token de rafraîchissement
     */
    public function verifyRefreshToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            // Vérifier le type de token (peut être 'refresh_token' ou 'refresh')
            if (!isset($decoded->type) || !in_array($decoded->type, ['refresh_token', 'refresh'])) {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log("Erreur vérification refresh token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Extraire le token du header Authorization
     */
    public function extractTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        
        // Debug: log tous les headers reçus
        error_log("=== DEBUG AUTH ===");
        error_log("Tous les headers: " . print_r($headers, true));
        error_log("SERVER Authorization: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'non défini'));
        
        // Essayer plusieurs méthodes pour obtenir le header Authorization
        $authHeader = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            error_log("Header trouvé via getallheaders['Authorization']");
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
            error_log("Header trouvé via getallheaders['authorization']");
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            error_log("Header trouvé via _SERVER['HTTP_AUTHORIZATION']");
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            error_log("Header trouvé via _SERVER['REDIRECT_HTTP_AUTHORIZATION']");
        }
        
        if (!$authHeader) {
            error_log("Aucun header Authorization trouvé!");
            return null;
        }
        
        error_log("Auth header value: " . $authHeader);

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Obtenir l'ID utilisateur depuis le token
     */
    public function getUserIdFromToken(): ?int
    {
        $token = $this->extractTokenFromHeader();
        if (!$token) {
            return null;
        }

        $decoded = $this->verifyToken($token);
        if (!$decoded || !isset($decoded->user_id)) {
            return null;
        }

        return (int) $decoded->user_id;
    }

    /**
     * Vérifier si un token est expiré
     */
    public function isTokenExpired(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return $decoded->exp < time();
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Hacher un mot de passe
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Vérifier un mot de passe
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Générer un token de vérification d'email
     */
    public function generateEmailVerificationToken(int $userId, string $email): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + 86400; // 24 heures

        $payload = [
            'iss' => 'finance-flow-api',
            'aud' => 'finance-flow-client',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'user_id' => $userId,
            'email' => $email,
            'type' => 'email_verification'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Vérifier un token de vérification d'email
     */
    public function verifyEmailVerificationToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            if (!isset($decoded->type) || $decoded->type !== 'email_verification') {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log("Erreur vérification token email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer un token de réinitialisation de mot de passe
     */
    public function generatePasswordResetToken(int $userId, string $email): string
    {
        $issuedAt = time();
        $expiration = $issuedAt + 3600; // 1 heure

        $payload = [
            'iss' => 'finance-flow-api',
            'aud' => 'finance-flow-client',
            'iat' => $issuedAt,
            'exp' => $expiration,
            'user_id' => $userId,
            'email' => $email,
            'type' => 'password_reset'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Vérifier un token de réinitialisation de mot de passe
     */
    public function verifyPasswordResetToken(string $token): ?object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
            if (!isset($decoded->type) || $decoded->type !== 'password_reset') {
                return null;
            }

            return $decoded;
        } catch (Exception $e) {
            error_log("Erreur vérification token reset: " . $e->getMessage());
            return null;
        }
    }
}