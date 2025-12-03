<?php

namespace FinanceFlow\Services;

use FinanceFlow\Repositories\PlannedTransactionRepository;
use FinanceFlow\Repositories\TransactionRepository;
use FinanceFlow\Models\PlannedTransaction;
use FinanceFlow\Services\{ValidationService, TransactionService};
use FinanceFlow\DTOs\PlannedTransaction\{CreatePlannedTransactionRequest, UpdatePlannedTransactionRequest, PlannedTransactionResponse};
use FinanceFlow\DTOs\Transaction\CreateTransactionRequest;
use Exception;

/**
 * Service pour la gestion des transactions récurrentes/planifiées
 * Suit le même pattern que TransactionService
 */
class PlannedTransactionService
{
    private PlannedTransactionRepository $plannedTransactionRepository;
    private TransactionService $transactionService;
    private ValidationService $validator;

    public function __construct()
    {
        $this->plannedTransactionRepository = new PlannedTransactionRepository();
        $this->transactionService = new TransactionService();
        $this->validator = new ValidationService();
    }

    /**
     * Créer une nouvelle transaction planifiée
     */
    public function create(CreatePlannedTransactionRequest $request, int $userId): PlannedTransaction
    {
        try {
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            $data = $this->prepareDataFromDTO($request, $userId);
            $id = $this->plannedTransactionRepository->create($data);

            if (!$id) {
                throw new Exception('Erreur lors de la création de la transaction planifiée', 500);
            }

            $plannedTransaction = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
            if (!$plannedTransaction) {
                throw new Exception('Transaction planifiée créée mais impossible de la récupérer', 500);
            }

            return $plannedTransaction;

        } catch (Exception $e) {
            error_log("Erreur création planned_transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer toutes les transactions planifiées d'un utilisateur
     * @return PlannedTransaction[]
     */
    public function getAll(int $userId, bool $activeOnly = false): array
    {
        return $this->plannedTransactionRepository->getAllByUserId($userId, $activeOnly);
    }

    /**
     * Récupérer une transaction planifiée par ID
     */
    public function getById(int $id, int $userId): PlannedTransaction
    {
        $plannedTransaction = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
        if (!$plannedTransaction) {
            throw new Exception('Transaction planifiée non trouvée', 404);
        }
        return $plannedTransaction;
    }

    /**
     * Mettre à jour une transaction planifiée
     */
    public function update(int $id, UpdatePlannedTransactionRequest $request, int $userId): PlannedTransaction
    {
        try {
            // Vérifier que la transaction existe
            $existing = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
            if (!$existing) {
                throw new Exception('Transaction planifiée non trouvée', 404);
            }

            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            if (!$request->hasUpdates()) {
                throw new Exception('Aucune donnée à mettre à jour', 400);
            }

            $updateData = $request->toArray();
            $updated = $this->plannedTransactionRepository->update($id, $updateData);
            
            if (!$updated) {
                throw new Exception('Erreur lors de la mise à jour', 500);
            }

            return $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);

        } catch (Exception $e) {
            error_log("Erreur mise à jour planned_transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer une transaction planifiée
     */
    public function delete(int $id, int $userId): bool
    {
        try {
            $existing = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
            if (!$existing) {
                throw new Exception('Transaction planifiée non trouvée', 404);
            }

            return $this->plannedTransactionRepository->delete($id);

        } catch (Exception $e) {
            error_log("Erreur suppression planned_transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Activer/Désactiver une transaction planifiée
     */
    public function toggleActive(int $id, int $userId): PlannedTransaction
    {
        try {
            $existing = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
            if (!$existing) {
                throw new Exception('Transaction planifiée non trouvée', 404);
            }

            $newStatus = !$existing->isActive();
            $this->plannedTransactionRepository->setActive($id, $newStatus);

            return $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);

        } catch (Exception $e) {
            error_log("Erreur toggleActive: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les transactions à exécuter (date dépassée)
     * @return PlannedTransaction[]
     */
    public function getDueTransactions(int $userId): array
    {
        return $this->plannedTransactionRepository->getDueTransactions($userId);
    }

    /**
     * Récupérer les prochaines transactions planifiées
     * @return PlannedTransaction[]
     */
    public function getUpcoming(int $userId, int $days = 30): array
    {
        return $this->plannedTransactionRepository->getUpcoming($userId, $days);
    }

    /**
     * Récupérer par type (revenus ou dépenses)
     * @return PlannedTransaction[]
     */
    public function getByType(int $userId, string $type): array
    {
        if (!in_array($type, ['income', 'expense'])) {
            throw new Exception('Type invalide. Valeurs acceptées: income, expense', 400);
        }
        return $this->plannedTransactionRepository->getByOperationType($userId, $type);
    }

    /**
     * Exécuter une transaction planifiée (créer la transaction réelle)
     */
    public function executeTransaction(int $id, int $userId): array
    {
        try {
            $planned = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);
            if (!$planned) {
                throw new Exception('Transaction planifiée non trouvée', 404);
            }

            if (!$planned->isActive()) {
                throw new Exception('Cette transaction planifiée est désactivée', 400);
            }

            // Créer la transaction réelle via le TransactionService existant
            $transactionRequest = CreateTransactionRequest::fromArray([
                'title' => $planned->getTitle(),
                'description' => $planned->getDescription(),
                'amount' => $planned->getSignedAmount(), // Montant signé
                'date' => date('Y-m-d'), // Date d'exécution = aujourd'hui
                'category_id' => $planned->getCategoryId(),
                'sub_category_id' => $planned->getSubCategoryId(),
                'account_id' => $planned->getAccountId()
            ]);

            $transaction = $this->transactionService->createTransaction($transactionRequest, $userId);

            // Mettre à jour la prochaine date d'exécution
            $nextDate = $planned->calculateNextExecutionDate();
            $this->plannedTransactionRepository->updateNextDate($id, $nextDate);

            // Récupérer la transaction planifiée mise à jour
            $updatedPlanned = $this->plannedTransactionRepository->findByIdAndUserId($id, $userId);

            return [
                'transaction' => $transaction->toArray(),
                'planned_transaction' => $updatedPlanned->toArray(),
                'next_execution_date' => $nextDate
            ];

        } catch (Exception $e) {
            error_log("Erreur executeTransaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécuter toutes les transactions dues pour un utilisateur
     */
    public function executeDueTransactions(int $userId): array
    {
        try {
            $dueTransactions = $this->getDueTransactions($userId);
            $results = [
                'executed' => [],
                'failed' => [],
                'total_executed' => 0,
                'total_failed' => 0
            ];

            foreach ($dueTransactions as $planned) {
                try {
                    $result = $this->executeTransaction($planned->getId(), $userId);
                    $results['executed'][] = [
                        'planned_id' => $planned->getId(),
                        'title' => $planned->getTitle(),
                        'transaction_id' => $result['transaction']['id']
                    ];
                    $results['total_executed']++;
                } catch (Exception $e) {
                    $results['failed'][] = [
                        'planned_id' => $planned->getId(),
                        'title' => $planned->getTitle(),
                        'error' => $e->getMessage()
                    ];
                    $results['total_failed']++;
                }
            }

            return $results;

        } catch (Exception $e) {
            error_log("Erreur executeDueTransactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculer la projection mensuelle
     */
    public function getMonthlyProjection(int $userId): array
    {
        return $this->plannedTransactionRepository->calculateMonthlyProjection($userId);
    }

    /**
     * Récupérer les statistiques des transactions planifiées
     * Optimisé: utilise une seule requête SQL agrégée
     */
    public function getStats(int $userId): array
    {
        try {
            // Utilise la méthode optimisée du repository (1 seule requête)
            $stats = $this->plannedTransactionRepository->getAggregatedStats($userId);
            $projection = $this->getMonthlyProjection($userId);

            return array_merge($stats, ['monthly_projection' => $projection]);

        } catch (Exception $e) {
            error_log("Erreur getStats planned_transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Préparer les données depuis un DTO pour la création
     */
    private function prepareDataFromDTO(CreatePlannedTransactionRequest $request, int $userId): array
    {
        return [
            'title' => trim($request->title),
            'description' => $request->description ? trim($request->description) : null,
            'amount' => $request->amount,
            'operation_type' => $request->operation_type,
            'frequency' => $request->frequency,
            'next_date' => $request->next_date,
            'interest_rate' => $request->interest_rate,
            'duration' => $request->duration,
            'duration_unit' => $request->duration_unit,
            'category_id' => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'user_id' => $userId,
            'account_id' => $request->account_id,
            'active' => $request->active
        ];
    }
}
