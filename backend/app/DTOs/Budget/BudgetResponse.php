<?php

namespace FinanceFlow\DTOs\Budget;

/**
 * DTO pour la réponse Budget
 */
class BudgetResponse
{
    public function __construct(
        public readonly int $id,
        public readonly float $max_amount,
        public readonly ?int $category_id,
        public readonly int $user_id,
        public readonly ?string $category_name = null,
        public readonly ?float $spent_amount = null,
        public readonly ?float $remaining_amount = null,
        public readonly ?float $usage_percentage = null,
        public readonly ?string $status = null
    ) {}

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            max_amount: (float) $data['max_amount'],
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            user_id: (int) $data['user_id'],
            category_name: $data['category_name'] ?? null,
            spent_amount: isset($data['spent_amount']) ? (float) $data['spent_amount'] : null,
            remaining_amount: isset($data['remaining_amount']) ? (float) $data['remaining_amount'] : null,
            usage_percentage: isset($data['usage_percentage']) ? (float) $data['usage_percentage'] : null,
            status: $data['status'] ?? null
        );
    }

    /**
     * Convertir en array pour réponse JSON
     */
    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'max_amount' => $this->max_amount,
            'max_amount_formatted' => number_format($this->max_amount, 2, ',', ' ') . '€',
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category_name
            ],
            'user_id' => $this->user_id
        ];

        // Ajouter les informations d'utilisation si disponibles
        if ($this->spent_amount !== null) {
            $data['spent_amount'] = $this->spent_amount;
            $data['spent_amount_formatted'] = number_format($this->spent_amount, 2, ',', ' ') . '€';
        }

        if ($this->remaining_amount !== null) {
            $data['remaining_amount'] = $this->remaining_amount;
            $data['remaining_amount_formatted'] = number_format($this->remaining_amount, 2, ',', ' ') . '€';
        }

        if ($this->usage_percentage !== null) {
            $data['usage_percentage'] = round($this->usage_percentage, 2);
            $data['usage_percentage_formatted'] = round($this->usage_percentage, 2) . '%';
        }

        if ($this->status !== null) {
            $data['status'] = $this->status;
        }

        return $data;
    }

    /**
     * Version minimale pour les listes
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'max_amount' => $this->max_amount,
            'max_amount_formatted' => number_format($this->max_amount, 2, ',', ' ') . '€',
            'category_name' => $this->category_name,
            'usage_percentage' => $this->usage_percentage ? round($this->usage_percentage, 2) : 0,
            'status' => $this->status ?? 'ok'
        ];
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedMaxAmount(): string
    {
        return number_format($this->max_amount, 2, ',', ' ') . '€';
    }

    /**
     * Vérifier si le budget a une catégorie
     */
    public function hasCategory(): bool
    {
        return $this->category_id !== null;
    }

    /**
     * Vérifier si le budget est dépassé
     */
    public function isExceeded(): bool
    {
        if ($this->spent_amount === null) {
            return false;
        }
        return $this->spent_amount > $this->max_amount;
    }

    /**
     * Vérifier si le budget est proche d'être dépassé (>= 80%)
     */
    public function isNearLimit(): bool
    {
        if ($this->usage_percentage === null) {
            return false;
        }
        return $this->usage_percentage >= 80 && $this->usage_percentage < 100;
    }
}
