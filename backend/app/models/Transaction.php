<?php

namespace FinanceFlow\Models;

/**
 * Modèle Transaction
 * Représente une transaction financière dans l'application
 */
class Transaction
{
    private ?int $id = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?float $amount = null;
    private ?string $date = null;
    private ?string $location = null;
    private ?int $categoryId = null;
    private ?int $subCategoryId = null;
    private ?int $userId = null;
    private ?int $accountId = null;
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

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getLocation(): ?string
    {
        return $this->location;
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

    public function setDate(?string $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
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
     * Convertir la transaction en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'date' => $this->date,
            'location' => $this->location,
            'category_id' => $this->categoryId,
            'sub_category_id' => $this->subCategoryId,
            'user_id' => $this->userId,
            'account_id' => $this->accountId,
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
        $this->date = $data['date'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $this->subCategoryId = isset($data['sub_category_id']) ? (int) $data['sub_category_id'] : null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->accountId = isset($data['account_id']) ? (int) $data['account_id'] : null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        return $this;
    }

    /**
     * Créer un objet Transaction depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        return (new self())->loadFromArray($data);
    }
}
