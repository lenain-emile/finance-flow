<?php

namespace FinanceFlow\DTOs\PlannedTransaction;

/**
 * DTO pour les requêtes POST de création de transaction récurrente
 */
class CreatePlannedTransactionRequest
{
    public const VALID_FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];
    public const VALID_OPERATION_TYPES = ['income', 'expense'];
    public const VALID_DURATION_UNITS = ['day', 'month', 'year'];

    public function __construct(
        public readonly string $title,
        public readonly float $amount,
        public readonly string $operation_type,
        public readonly string $frequency,
        public readonly string $next_date,
        public readonly ?string $description = null,
        public readonly ?float $interest_rate = null,
        public readonly ?int $duration = null,
        public readonly ?string $duration_unit = null,
        public readonly ?int $category_id = null,
        public readonly ?int $sub_category_id = null,
        public readonly ?int $account_id = null,
        public readonly bool $active = true
    ) {}

    /**
     * Créer depuis un array (validation basique)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            operation_type: $data['operation_type'] ?? '',
            frequency: $data['frequency'] ?? '',
            next_date: $data['next_date'] ?? date('Y-m-d'),
            description: $data['description'] ?? null,
            interest_rate: isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            duration_unit: $data['duration_unit'] ?? null,
            category_id: isset($data['category_id']) && !empty($data['category_id']) ? (int) $data['category_id'] : null,
            sub_category_id: isset($data['sub_category_id']) && !empty($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
            account_id: isset($data['account_id']) && !empty($data['account_id']) ? (int) $data['account_id'] : null,
            active: $data['active'] ?? true
        );
    }

    /**
     * Convertir en array pour traitement
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'amount' => $this->amount,
            'operation_type' => $this->operation_type,
            'frequency' => $this->frequency,
            'next_date' => $this->next_date,
            'description' => $this->description,
            'interest_rate' => $this->interest_rate,
            'duration' => $this->duration,
            'duration_unit' => $this->duration_unit,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'account_id' => $this->account_id,
            'active' => $this->active
        ];
    }

    /**
     * Validation des champs requis
     */
    public function isValid(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors['title'] = 'Le titre est requis';
        } elseif (strlen($this->title) > 150) {
            $errors['title'] = 'Le titre ne peut pas dépasser 150 caractères';
        }

        if ($this->amount <= 0) {
            $errors['amount'] = 'Le montant doit être positif';
        }

        if (empty($this->operation_type)) {
            $errors['operation_type'] = 'Le type d\'opération est requis';
        } elseif (!in_array($this->operation_type, self::VALID_OPERATION_TYPES)) {
            $errors['operation_type'] = 'Type d\'opération invalide. Valeurs acceptées: ' . implode(', ', self::VALID_OPERATION_TYPES);
        }

        if (empty($this->frequency)) {
            $errors['frequency'] = 'La fréquence est requise';
        } elseif (!in_array($this->frequency, self::VALID_FREQUENCIES)) {
            $errors['frequency'] = 'Fréquence invalide. Valeurs acceptées: ' . implode(', ', self::VALID_FREQUENCIES);
        }

        if (empty($this->next_date)) {
            $errors['next_date'] = 'La prochaine date est requise';
        } elseif (!strtotime($this->next_date)) {
            $errors['next_date'] = 'Format de date invalide';
        }

        if ($this->description && strlen($this->description) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères';
        }

        if ($this->interest_rate !== null && ($this->interest_rate < 0 || $this->interest_rate > 100)) {
            $errors['interest_rate'] = 'Le taux d\'intérêt doit être entre 0 et 100';
        }

        if ($this->duration !== null && $this->duration < 1) {
            $errors['duration'] = 'La durée doit être supérieure à 0';
        }

        if ($this->duration_unit !== null && !in_array($this->duration_unit, self::VALID_DURATION_UNITS)) {
            $errors['duration_unit'] = 'Unité de durée invalide. Valeurs acceptées: ' . implode(', ', self::VALID_DURATION_UNITS);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
