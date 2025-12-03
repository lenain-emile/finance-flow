<?php

namespace FinanceFlow\DTOs\PlannedTransaction;

/**
 * DTO pour les requêtes PUT de mise à jour de transaction récurrente
 */
class UpdatePlannedTransactionRequest
{
    public const VALID_FREQUENCIES = ['daily', 'weekly', 'monthly', 'yearly'];
    public const VALID_OPERATION_TYPES = ['income', 'expense'];
    public const VALID_DURATION_UNITS = ['day', 'month', 'year'];

    public function __construct(
        public readonly ?string $title = null,
        public readonly ?float $amount = null,
        public readonly ?string $operation_type = null,
        public readonly ?string $frequency = null,
        public readonly ?string $next_date = null,
        public readonly ?string $description = null,
        public readonly ?float $interest_rate = null,
        public readonly ?int $duration = null,
        public readonly ?string $duration_unit = null,
        public readonly ?int $category_id = null,
        public readonly ?int $sub_category_id = null,
        public readonly ?int $account_id = null,
        public readonly ?bool $active = null
    ) {}

    /**
     * Créer depuis un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            operation_type: $data['operation_type'] ?? null,
            frequency: $data['frequency'] ?? null,
            next_date: $data['next_date'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            interest_rate: array_key_exists('interest_rate', $data) ? (isset($data['interest_rate']) ? (float) $data['interest_rate'] : null) : null,
            duration: array_key_exists('duration', $data) ? (isset($data['duration']) ? (int) $data['duration'] : null) : null,
            duration_unit: array_key_exists('duration_unit', $data) ? $data['duration_unit'] : null,
            category_id: array_key_exists('category_id', $data) ? (isset($data['category_id']) ? (int) $data['category_id'] : null) : null,
            sub_category_id: array_key_exists('sub_category_id', $data) ? (isset($data['sub_category_id']) ? (int) $data['sub_category_id'] : null) : null,
            account_id: array_key_exists('account_id', $data) ? (isset($data['account_id']) ? (int) $data['account_id'] : null) : null,
            active: isset($data['active']) ? (bool) $data['active'] : null
        );
    }

    /**
     * Vérifier s'il y a des données à mettre à jour
     */
    public function hasUpdates(): bool
    {
        return $this->title !== null
            || $this->amount !== null
            || $this->operation_type !== null
            || $this->frequency !== null
            || $this->next_date !== null
            || $this->description !== null
            || $this->interest_rate !== null
            || $this->duration !== null
            || $this->duration_unit !== null
            || $this->category_id !== null
            || $this->sub_category_id !== null
            || $this->account_id !== null
            || $this->active !== null;
    }

    /**
     * Convertir en array (seulement les valeurs non-null)
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->title !== null) $data['title'] = $this->title;
        if ($this->amount !== null) $data['amount'] = $this->amount;
        if ($this->operation_type !== null) $data['operation_type'] = $this->operation_type;
        if ($this->frequency !== null) $data['frequency'] = $this->frequency;
        if ($this->next_date !== null) $data['next_date'] = $this->next_date;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->interest_rate !== null) $data['interest_rate'] = $this->interest_rate;
        if ($this->duration !== null) $data['duration'] = $this->duration;
        if ($this->duration_unit !== null) $data['duration_unit'] = $this->duration_unit;
        if ($this->category_id !== null) $data['category_id'] = $this->category_id;
        if ($this->sub_category_id !== null) $data['sub_category_id'] = $this->sub_category_id;
        if ($this->account_id !== null) $data['account_id'] = $this->account_id;
        if ($this->active !== null) $data['active'] = $this->active;
        
        return $data;
    }

    /**
     * Validation des champs (mode update : valide seulement les champs présents)
     */
    public function isValid(): array
    {
        $errors = [];

        if ($this->title !== null) {
            if (empty($this->title)) {
                $errors['title'] = 'Le titre ne peut pas être vide';
            } elseif (strlen($this->title) > 150) {
                $errors['title'] = 'Le titre ne peut pas dépasser 150 caractères';
            }
        }

        if ($this->amount !== null && $this->amount <= 0) {
            $errors['amount'] = 'Le montant doit être positif';
        }

        if ($this->operation_type !== null && !in_array($this->operation_type, self::VALID_OPERATION_TYPES)) {
            $errors['operation_type'] = 'Type d\'opération invalide. Valeurs acceptées: ' . implode(', ', self::VALID_OPERATION_TYPES);
        }

        if ($this->frequency !== null && !in_array($this->frequency, self::VALID_FREQUENCIES)) {
            $errors['frequency'] = 'Fréquence invalide. Valeurs acceptées: ' . implode(', ', self::VALID_FREQUENCIES);
        }

        if ($this->next_date !== null && !strtotime($this->next_date)) {
            $errors['next_date'] = 'Format de date invalide';
        }

        if ($this->description !== null && strlen($this->description) > 1000) {
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
