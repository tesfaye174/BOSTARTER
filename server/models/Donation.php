<?php

class Donation {
    private int $id;
    private int $user_id;
    private int $project_id;
    private float $amount;
    private string $created_at;

    public function __construct(int $id, int $user_id, int $project_id, float $amount, string $created_at) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->project_id = $project_id;
        $this->amount = $amount;
        $this->created_at = $created_at;
    }

    public static function fromArray(array $data): self {
        return new self(
            $data['id'] ?? 0,
            $data['user_id'],
            $data['project_id'],
            $data['amount'],
            $data['created_at'] ?? date('Y-m-d H:i:s')
        );
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'amount' => $this->amount,
            'created_at' => $this->created_at
        ];
    }

    // Getters
    public function getId(): int {
        return $this->id;
    }

    public function getUserId(): int {
        return $this->user_id;
    }

    public function getProjectId(): int {
        return $this->project_id;
    }

    public function getAmount(): float {
        return $this->amount;
    }

    public function getCreatedAt(): string {
        return $this->created_at;
    }
} 