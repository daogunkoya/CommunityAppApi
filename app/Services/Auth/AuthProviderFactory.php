<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Contracts\AuthProviderInterface;
use App\Enums\AuthType;
use App\Services\Auth\Providers\EmailAuthAdapter;
use App\Services\Auth\Providers\GoogleAuthAdapter;
use App\Services\Auth\Providers\FacebookAuthAdapter;
use App\Services\Auth\Providers\AppleAuthAdapter;
use InvalidArgumentException;

class AuthProviderFactory
{
    public function create(AuthType $authType): AuthProviderInterface
    {
        return match($authType) {
            AuthType::EMAIL => new EmailAuthAdapter(),
            AuthType::GOOGLE => new GoogleAuthAdapter(),
            AuthType::FACEBOOK => new FacebookAuthAdapter(),
            AuthType::APPLE => new AppleAuthAdapter(),
        };
    }
}
