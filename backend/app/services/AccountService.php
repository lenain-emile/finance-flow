<?php

namespace FinanceFlow\Services;

use FinanceFlow\Repositories\AccountRepository;
use FinanceFlow\Models\Account;
use FinanceFlow\DTOs\Account\CreateAccountRequest;
use FinanceFlow\DTOs\Account\UpdateAccountRequest;
use FinanceFlow\DTOs\Account\AccountResponse;
use Exception;

/**
 * Service pour la gestion des comptes
 */
class AccountService
{
    private AccountRepository $accountRepository;

    public function __construct()
    {
        $this->accountRepository = new AccountRepository();
    }

    /**
     * Créer un nouveau compte
     */
    public function createAccount(CreateAccountRequest $request, int $userId): Account
    {
        try {
            // Validation
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Préparer les données
            $accountData = $request->toArray();
            $accountData['user_id'] = $userId;

            // Créer le compte
            $accountId = $this->accountRepository->create($accountData);

            if (!$accountId) {
                throw new Exception('Erreur lors de la création du compte', 500);
            }

            // Récupérer le compte créé
            $account = $this->accountRepository->findByIdAndUserId($accountId, $userId);
            if (!$account) {
                throw new Exception('Compte créé mais impossible de le récupérer', 500);
            }

            return $account;

        } catch (Exception $e) {
            error_log("Erreur création compte: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour un compte
     */
    public function updateAccount(int $accountId, UpdateAccountRequest $request, int $userId): Account
    {
        try {
            // Validation
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Vérifier qu'il y a des changements
            if (!$request->hasChanges()) {
                throw new Exception('Aucune modification à apporter', 422);
            }

            // Vérifier que le compte existe et appartient à l'utilisateur
            $account = $this->accountRepository->findByIdAndUserId($accountId, $userId);
            if (!$account) {
                throw new Exception('Compte non trouvé', 404);
            }

            // Mettre à jour
            $updateData = $request->toArray();
            $success = $this->accountRepository->update($accountId, $updateData);

            if (!$success) {
                throw new Exception('Erreur lors de la mise à jour du compte', 500);
            }

            // Récupérer le compte mis à jour
            $account = $this->accountRepository->findByIdAndUserId($accountId, $userId);
            if (!$account) {
                throw new Exception('Compte mis à jour mais impossible de le récupérer', 500);
            }

            return $account;

        } catch (Exception $e) {
            error_log("Erreur mise à jour compte: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un compte
     */
    public function deleteAccount(int $accountId, int $userId): bool
    {
        try {
            // Vérifier que le compte existe et appartient à l'utilisateur
            $account = $this->accountRepository->findByIdAndUserId($accountId, $userId);
            if (!$account) {
                throw new Exception('Compte non trouvé', 404);
            }

            // Supprimer
            return $this->accountRepository->delete($accountId);

        } catch (Exception $e) {
            error_log("Erreur suppression compte: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer un compte avec son solde
     */
    public function getAccount(int $accountId, int $userId): AccountResponse
    {
        try {
            $accountData = $this->accountRepository->getAccountWithBalance($accountId, $userId);
            
            if (!$accountData) {
                throw new Exception('Compte non trouvé', 404);
            }

            return AccountResponse::fromArray($accountData);

        } catch (Exception $e) {
            error_log("Erreur récupération compte: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer tous les comptes d'un utilisateur avec leurs soldes
     */
    public function getAllAccounts(int $userId): array
    {
        try {
            $accountsData = $this->accountRepository->getAllAccountsWithBalances($userId);
            
            return array_map(
                fn($data) => AccountResponse::fromArray($data),
                $accountsData
            );

        } catch (Exception $e) {
            error_log("Erreur récupération comptes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir le solde actuel d'un compte
     */
    public function getAccountBalance(int $accountId, int $userId): float
    {
        try {
            $balance = $this->accountRepository->getCurrentBalance($accountId, $userId);
            
            if ($balance === null) {
                throw new Exception('Compte non trouvé', 404);
            }

            return $balance;

        } catch (Exception $e) {
            error_log("Erreur récupération solde: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifier si un compte a un solde suffisant pour une transaction
     */
    public function hasSufficientBalance(int $accountId, int $userId, float $amount): array
    {
        try {
            $currentBalance = $this->getAccountBalance($accountId, $userId);
            $newBalance = $currentBalance + $amount;
            $isSufficient = $newBalance >= 0;

            return [
                'sufficient' => $isSufficient,
                'current_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'message' => $isSufficient 
                    ? 'Solde suffisant' 
                    : sprintf('Solde insuffisant. Solde actuel: %.2f€, Nouveau solde: %.2f€', $currentBalance, $newBalance)
            ];

        } catch (Exception $e) {
            error_log("Erreur vérification solde: " . $e->getMessage());
            throw $e;
        }
    }
}
