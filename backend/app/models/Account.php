<?php

namespace FinanceFlow\Models;

/**
 * Modèle Account
 * Représente un compte bancaire avec solde initial
 */
class Account
{
    private ?int $id = null;
    private string $name;
    private float $initialBalance;
    private string $type;
    private int $userId;

    public function __construct(
        string $name,
        float $initialBalance,
        string $type,
        int $userId,
        ?int $id = null
    ) {
        $this->name = $name;
        $this->initialBalance = $initialBalance;
        $this->type = $type;
        $this->userId = $userId;
        $this->id = $id;
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInitialBalance(): float
    {
        return $this->initialBalance;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    // Setters
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Convertir l'objet en tableau
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initial_balance' => $this->initialBalance,
            'type' => $this->type,
            'user_id' => $this->userId
        ];
    }

    /**
     * Créer un objet Account depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name'],
            (float) $data['initial_balance'],
            $data['type'],
            (int) $data['user_id'],
            isset($data['id']) ? (int) $data['id'] : null
        );
    }
}
