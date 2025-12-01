<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Core\Response;
use FinanceFlow\Services\AccountService;
use FinanceFlow\DTOs\Account\CreateAccountRequest;
use FinanceFlow\DTOs\Account\UpdateAccountRequest;
use Exception;

/**
 * Contrôleur pour la gestion des comptes
 */
class AccountController
{
    private AccountService $accountService;

    public function __construct()
    {
        $this->accountService = new AccountService();
    }

    /**
     * Créer un nouveau compte
     * POST /api/accounts
     */
    public function create(): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Récupérer et valider les données
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new CreateAccountRequest($data);

            // Créer le compte
            $account = $this->accountService->createAccount($request, $userId);

            Response::success(
                ['account' => $account->toArray()],
                'Compte créé avec succès',
                201
            );

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Récupérer tous les comptes de l'utilisateur
     * GET /api/accounts
     */
    public function index(): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Récupérer les comptes avec leurs soldes
            $accounts = $this->accountService->getAllAccounts($userId);

            Response::success([
                'accounts' => array_map(fn($account) => $account->toArray(), $accounts),
                'count' => count($accounts)
            ]);

        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    /**
     * Récupérer un compte spécifique
     * GET /api/accounts/{id}
     */
    public function show(int $id): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Récupérer le compte avec son solde
            $account = $this->accountService->getAccount($id, $userId);

            Response::success([
                'account' => $account->toArray()
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Mettre à jour un compte
     * PUT /api/accounts/{id}
     */
    public function update(int $id): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Récupérer et valider les données
            $data = json_decode(file_get_contents('php://input'), true);
            $request = new UpdateAccountRequest($data);

            // Mettre à jour le compte
            $account = $this->accountService->updateAccount($id, $request, $userId);

            Response::success([
                'message' => 'Compte mis à jour avec succès',
                'account' => $account->toArray()
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Supprimer un compte
     * DELETE /api/accounts/{id}
     */
    public function delete(int $id): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Supprimer le compte
            $this->accountService->deleteAccount($id, $userId);

            Response::success([
                'message' => 'Compte supprimé avec succès'
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $code);
        }
    }

    /**
     * Obtenir le solde d'un compte
     * GET /api/accounts/{id}/balance
     */
    public function balance(int $id): void
    {
        try {
            // Récupérer l'utilisateur authentifié
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                Response::unauthorized('Non authentifié');
                return;
            }

            // Récupérer le solde
            $balance = $this->accountService->getAccountBalance($id, $userId);

            Response::success([
                'account_id' => $id,
                'balance' => $balance
            ]);

        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $code);
        }
    }
}
