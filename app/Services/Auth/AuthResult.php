<?php

namespace App\Services\Auth;

use App\Contracts\AuthResultInterface;
use App\Models\User;

class AuthResult implements AuthResultInterface
{
    public function __construct(
        private bool $successful,
        private ?User $user = null,
        private ?string $token = null,
        private ?string $error = null
    ) {}

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public static function success(User $user, string $token): self
    {
        return new self(true, $user, $token);
    }

    public static function failure(string $error): self
    {
        return new self(false, error: $error);
    }
}
