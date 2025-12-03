<?php

namespace FinanceFlow\DTOs\PlannedTransaction;

/**
 * DTO pour les réponses de transaction récurrente
 */
class PlannedTransactionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly float $amount,
        public readonly string $operation_type,
        public readonly string $frequency,
        public readonly string $next_date,
        public readonly ?string $description,
        public readonly ?float $interest_rate,
        public readonly ?int $duration,
        public readonly ?string $duration_unit,
        public readonly ?int $category_id,
        public readonly ?int $sub_category_id,
        public readonly ?int $account_id,
        public readonly bool $active,
        public readonly ?string $created_at,
        public readonly ?string $updated_at,
        // Champs calculés
        public readonly ?string $frequency_label = null,
        public readonly ?string $operation_type_label = null,
        public readonly ?float $signed_amount = null
    ) {}

    /**
     * Labels en français pour les fréquences
     */
    private const FREQUENCY_LABELS = [
        'daily' => 'Quotidien',
        'weekly' => 'Hebdomadaire',
        'monthly' => 'Mensuel',
        'yearly' => 'Annuel'
    ];

    /**
     * Labels en français pour les types d'opération
     */
    private const OPERATION_TYPE_LABELS = [
        'income' => 'Revenu',
        'expense' => 'Dépense'
    ];

    /**
     * Créer depuis un array
     */
    public static function fromArray(array $data): self
    {
        $amount = (float) ($data['amount'] ?? 0);
        $operationType = $data['operation_type'] ?? 'expense';
        $frequency = $data['frequency'] ?? 'monthly';
        
        // Calculer le montant signé
        $signedAmount = $operationType === 'expense' ? -abs($amount) : abs($amount);

        return new self(
            id: (int) ($data['id'] ?? 0),
            title: $data['title'] ?? '',
            amount: $amount,
            operation_type: $operationType,
            frequency: $frequency,
            next_date: $data['next_date'] ?? '',
            description: $data['description'] ?? null,
            interest_rate: isset($data['interest_rate']) ? (float) $data['interest_rate'] : null,
            duration: isset($data['duration']) ? (int) $data['duration'] : null,
            duration_unit: $data['duration_unit'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            sub_category_id: isset($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
            account_id: isset($data['account_id']) ? (int) $data['account_id'] : null,
            active: (bool) ($data['active'] ?? true),
            created_at: $data['created_at'] ?? null,
            updated_at: $data['updated_at'] ?? null,
            frequency_label: self::FREQUENCY_LABELS[$frequency] ?? $frequency,
            operation_type_label: self::OPERATION_TYPE_LABELS[$operationType] ?? $operationType,
            signed_amount: $signedAmount
        );
    }

    /**
     * Convertir en array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'operation_type' => $this->operation_type,
            'operation_type_label' => $this->operation_type_label,
            'frequency' => $this->frequency,
            'frequency_label' => $this->frequency_label,
            'next_date' => $this->next_date,
            'description' => $this->description,
            'interest_rate' => $this->interest_rate,
            'duration' => $this->duration,
            'duration_unit' => $this->duration_unit,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'account_id' => $this->account_id,
            'active' => $this->active,
            'signed_amount' => $this->signed_amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
