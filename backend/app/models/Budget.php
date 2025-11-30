<?php

namespace FinanceFlow\Models;

/**
 * Modèle Budget
 * Représente un budget alloué à une catégorie pour un utilisateur
 */
class Budget
{
    private ?int $id = null;
    private ?float $maxAmount = null;
    private ?int $categoryId = null;
    private ?int $userId = null;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    // ==================== GETTERS ====================
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMaxAmount(): ?float
    {
        return $this->maxAmount;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
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

    public function setMaxAmount(?float $maxAmount): self
    {
        $this->maxAmount = $maxAmount;
        return $this;
    }

    public function setCategoryId(?int $categoryId): self
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
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
     * Convertir le budget en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'max_amount' => $this->maxAmount,
            'category_id' => $this->categoryId,
            'user_id' => $this->userId,
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
        $this->maxAmount = isset($data['max_amount']) ? (float) $data['max_amount'] : null;
        $this->categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $this->userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        return $this;
    }

    /**
     * Créer un objet Budget depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        return (new self())->loadFromArray($data);
    }

    /**
     * Vérifier si le budget a une catégorie assignée
     */
    public function hasCategory(): bool
    {
        return $this->categoryId !== null;
    }
}
