<?php

namespace App\Contracts;

use App\Models\User;

interface AuthProviderInterface
{
    /**
     * Authenticate user with the provider
     */
    public function authenticate(array $credentials): AuthResultInterface;

    /**
     * Verify token with the provider
     */
    public function verifyToken(string $token): bool;

    /**
     * Get user info from the provider
     */
    public function getUserInfo(string $token): array;
}
