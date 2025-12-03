<?php

namespace FinanceFlow\Models;

/**
 * Modèle PlannedTransaction
 * Représente une transaction récurrente/planifiée dans l'application
 * Réutilise la même structure que Transaction avec des champs supplémentaires
 */
class PlannedTransaction
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?float $amount = null;
    private ?string $operationType = null; // 'income' ou 'expense'
    private ?string $frequency = null; // 'daily', 'weekly', 'monthly', 'yearly'
    private ?string $nextDate = null;
    private ?float $interestRate = null;
    private ?int $duration = null;
    private ?string $durationUnit = null; // 'day', 'month', 'year'
    private ?int $categoryId = null;
    private ?int $subCategoryId = null;
    private ?int $userId = null;
    private ?int $accountId = null;
    private ?bool $active = true;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    // ==================== GETTERS ====================
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function getOperationType(): ?string
    {
        return $this->operationType;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function getNextDate(): ?string
    {
        return $this->nextDate;
    }

    public function getInterestRate(): ?float
    {
        return $this->interestRate;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function getDurationUnit(): ?string
    {
        return $this->durationUnit;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getSubCategoryId(): ?int
    {
        return $this->subCategoryId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // ==================== SETTERS (Fluent Interface) ====================
    
    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function setOperationType(?string $operationType): self
    {
        $this->operationType = $operationType;
        return $this;
    }

    public function setFrequency(?string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function setNextDate(?string $nextDate): self
    {
        $this->nextDate = $nextDate;
        return $this;
    }

    public function setInterestRate(?float $interestRate): self
    {
        $this->interestRate = $interestRate;
        return $this;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function setDurationUnit(?string $durationUnit): self
    {
        $this->durationUnit = $durationUnit;
        return $this;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function setSubCategoryId(?int $subCategoryId): self
    {
        $this->subCategoryId = $subCategoryId;
        return $this;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setAccountId(?int $accountId): self
    {
        $this->accountId = $accountId;
        return $this;
    }

    public function setActive(?bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // ==================== MÉTHODES DE TRANSFORMATION ====================
    
    /**
     * Convertir la transaction planifiée en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'operation_type' => $this->operationType,
            'frequency' => $this->frequency,
            'next_date' => $this->nextDate,
            'interest_rate' => $this->interestRate,
            'duration' => $this->duration,
            'duration_unit' => $this->durationUnit,
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'user_id' => $this->userId,
            'account_id' => $this->accountId,
            'active' => $this->active,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Charger les données depuis un tableau
     */
    public function loadFromArray(array $data): self
    {
        $this->id = $data['id'] ?? null;
        $this->title = $data['title'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->amount = isset($data['amount']) ? (float) $data['amount'] : null;
        $this->operationType = $data['operation_type'] ?? null;
        $this->frequency = $data['frequency'] ?? null;
        $this->nextDate = $data['next_date'] ?? null;
        $this->interestRate = isset($data['interest_rate']) ? (float) $data['interest_rate'] : null;
        $this->duration = isset($data['duration']) ? (int) $data['duration'] : null;
        $this->durationUnit = $data['duration_unit'] ?? null;
        $this->categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $this->subCategoryId = isset($data['sub_category_id']) ? (int) $data['sub_category_id'] : null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->accountId = isset($data['account_id']) ? (int) $data['account_id'] : null;
        $this->active = isset($data['active']) ? (bool) $data['active'] : true;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        return $this;
    }

    /**
     * Créer un objet PlannedTransaction depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        return (new self())->loadFromArray($data);
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Calculer la prochaine date d'exécution selon la fréquence
     */
    public function calculateNextExecutionDate(): string
    {
        $currentDate = $this->nextDate ?? date('Y-m-d');
        
        return match ($this->frequency) {
            'daily' => date('Y-m-d', strtotime($currentDate . ' +1 day')),
            'weekly' => date('Y-m-d', strtotime($currentDate . ' +1 week')),
            'monthly' => date('Y-m-d', strtotime($currentDate . ' +1 month')),
            'yearly' => date('Y-m-d', strtotime($currentDate . ' +1 year')),
            default => date('Y-m-d', strtotime($currentDate . ' +1 month'))
        };
    }

    /**
     * Vérifier si la transaction doit être exécutée aujourd'hui
     */
    public function isDueToday(): bool
    {
        return $this->active && $this->nextDate === date('Y-m-d');
    }

    /**
     * Vérifier si la transaction est en retard
     */
    public function isOverdue(): bool
    {
        return $this->active && $this->nextDate < date('Y-m-d');
    }

    /**
     * Obtenir le montant signé (négatif pour expense, positif pour income)
     */
    public function getSignedAmount(): float
    {
        if ($this->operationType === 'expense') {
            return -abs($this->amount ?? 0);
        }
        return abs($this->amount ?? 0);
    }
}
