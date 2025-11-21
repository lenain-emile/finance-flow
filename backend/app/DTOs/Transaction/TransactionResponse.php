<?php

namespace FinanceFlow\DTOs\Transaction;

/**
 * DTO pour les réponses GET de transaction
 * Représente les données transaction qui seront retournées au client
 */
class TransactionResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly float $amount,
        public readonly string $date,
        public readonly ?string $location,
        public readonly ?int $category_id,
        public readonly ?int $sub_category_id,
        public readonly int $user_id,
        public readonly ?int $account_id,
        public readonly ?string $category_name = null,
        public readonly ?string $sub_category_name = null,
        public readonly ?string $account_name = null
    ) {}

    /**
     * Créer depuis un array (données de la BDD)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            amount: (float) $data['amount'],
            date: $data['date'],
            location: $data['location'] ?? null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            sub_category_id: isset($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
            user_id: (int) $data['user_id'],
            account_id: isset($data['account_id']) ? (int) $data['account_id'] : null,
            category_name: $data['category_name'] ?? null,
            sub_category_name: $data['sub_category_name'] ?? null,
            account_name: $data['account_name'] ?? null
        );
    }

    /**
     * Convertir en array pour réponse JSON
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'amount_formatted' => number_format($this->amount, 2, ',', ' ') . '€',
            'date' => $this->date,
            'date_formatted' => date('d/m/Y', strtotime($this->date)),
            'location' => $this->location,
            'category' => [
                'id' => $this->category_id,
                'name' => $this->category_name
            ],
            'sub_category' => [
                'id' => $this->sub_category_id,
                'name' => $this->sub_category_name
            ],
            'account' => [
                'id' => $this->account_id,
                'name' => $this->account_name
            ],
            'user_id' => $this->user_id
        ];
    }

    /**
     * Version minimale pour les listes
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'amount' => $this->amount,
            'amount_formatted' => number_format($this->amount, 2, ',', ' ') . '€',
            'date' => $this->date,
            'date_formatted' => date('d/m/Y', strtotime($this->date)),
            'category_name' => $this->category_name,
            'location' => $this->location
        ];
    }

    /**
     * Obtenir le montant formaté
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2, ',', ' ') . '€';
    }

    /**
     * Obtenir la date formatée
     */
    public function getFormattedDate(): string
    {
        return date('d/m/Y', strtotime($this->date));
    }

    /**
     * Vérifier si la transaction a une catégorie
     */
    public function hasCategory(): bool
    {
        return $this->category_id !== null;
    }

    /**
     * Vérifier si la transaction a une sous-catégorie
     */
    public function hasSubCategory(): bool
    {
        return $this->sub_category_id !== null;
    }

    /**
     * Vérifier si la transaction a un compte associé
     */
    public function hasAccount(): bool
    {
        return $this->account_id !== null;
    }

    /**
     * Obtenir le nom complet de la catégorie (Category > SubCategory)
     */
    public function getFullCategoryName(): ?string
    {
        if (!$this->hasCategory()) {
            return null;
        }

        $categoryName = $this->category_name ?? "Catégorie #{$this->category_id}";
        
        if ($this->hasSubCategory()) {
            $subCategoryName = $this->sub_category_name ?? "Sous-catégorie #{$this->sub_category_id}";
            return $categoryName . ' > ' . $subCategoryName;
        }

        return $categoryName;
    }
}