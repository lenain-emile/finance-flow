<?php

namespace FinanceFlow\Repositories;

use FinanceFlow\Core\Repository;
use FinanceFlow\Models\Account;
use Exception;
use PDO;

/**
 * Repository pour les comptes
 * Hérite des méthodes communes de Repository et ajoute les spécificités comptes
 */
class AccountRepository extends Repository
{
    /**
     * Nom de la table account
     */
    protected function getTableName(): string
    {
        return 'account';
    }
    
    /**
     * Récupérer un compte par ID et user_id (sécurité)
     */
    public function findByIdAndUserId(int $id, int $userId): ?Account
    {
        $data = $this->findOneBy(['id' => $id, 'user_id' => $userId]);
        return $data ? Account::fromArray($data) : null;
    }
    
    /**
     * Récupérer tous les comptes d'un utilisateur
     */
    public function findByUserId(int $userId): array
    {
        try {
            $results = $this->findAllBy(['user_id' => $userId], ['id' => 'DESC']);
            
            // Convertir les tableaux en objets Account
            return array_map(fn($data) => Account::fromArray($data), $results);
            
        } catch (Exception $e) {
            error_log("Erreur findByUserId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculer le solde actuel d'un compte
     * Solde actuel = initial_balance + SUM(transactions.amount)
     * 
     * @param int $accountId ID du compte
     * @return float
     */
    private function calculateCurrentBalance(int $accountId): float
    {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM transaction 
                    WHERE account_id = :account_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['account_id' => $accountId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($result['total'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Erreur calculateCurrentBalance: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Récupérer un compte avec son solde actuel
     * 
     * @param int $accountId ID du compte
     * @param int $userId ID de l'utilisateur (sécurité)
     * @return array|null
     */
    public function getAccountWithBalance(int $accountId, int $userId): ?array
    {
        try {
            // Récupérer le compte
            $sql = "SELECT * FROM {$this->table} 
                    WHERE id = :account_id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            $accountData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$accountData) {
                return null;
            }
            
            // Calculer le solde actuel
            $transactionsTotal = $this->calculateCurrentBalance($accountId);
            $currentBalance = $accountData['initial_balance'] + $transactionsTotal;
            
            // Ajouter les informations calculées
            $accountData['current_balance'] = $currentBalance;
            $accountData['movements'] = $transactionsTotal;
            
            return $accountData;
            
        } catch (Exception $e) {
            error_log("Erreur getAccountWithBalance: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer tous les comptes d'un utilisateur avec leurs soldes actuels
     */
    public function getAllAccountsWithBalances(int $userId): array
    {
        try {
            // Récupérer tous les comptes de l'utilisateur
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id
                    ORDER BY id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajouter le solde actuel pour chaque compte
            foreach ($accounts as &$account) {
                $transactionsTotal = $this->calculateCurrentBalance($account['id']);
                $account['current_balance'] = $account['initial_balance'] + $transactionsTotal;
                $account['movements'] = $transactionsTotal;
            }
            
            return $accounts;
            
        } catch (Exception $e) {
            error_log("Erreur getAllAccountsWithBalances: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifier si un compte appartient bien à un utilisateur
     */
    public function belongsToUser(int $accountId, int $userId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE id = :account_id AND user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'account_id' => $accountId,
                'user_id' => $userId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int) $result['count']) > 0;
            
        } catch (Exception $e) {
            error_log("Erreur belongsToUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir le solde actuel d'un compte
     */
    public function getCurrentBalance(int $accountId, int $userId): ?float
    {
        try {
            $accountData = $this->getAccountWithBalance($accountId, $userId);
            return $accountData ? $accountData['current_balance'] : null;
            
        } catch (Exception $e) {
            error_log("Erreur getCurrentBalance: " . $e->getMessage());
            return null;
        }
    }
}
