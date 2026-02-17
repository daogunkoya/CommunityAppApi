<?php

namespace App\Contracts;

use App\Models\User;

interface AuthResultInterface
{
    /**
     * Check if authentication was successful
     */
    public function isSuccessful(): bool;

    /**
     * Get the authenticated user
     */
    public function getUser(): ?User;

    /**
     * Get the access token
     */
    public function getToken(): ?string;

    /**
     * Get error message if authentication failed
     */
    public function getError(): ?string;
}
