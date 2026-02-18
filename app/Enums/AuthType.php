<?php

declare(strict_types=1);

namespace App\Enums;

enum AuthType: string
{
    case EMAIL = 'email';
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';
    case APPLE = 'apple';

    public static function fromInt(int $value): self
    {
        return match ($value) {
            1 => self::EMAIL,
            2 => self::GOOGLE,
            3 => self::FACEBOOK,
            4 => self::APPLE,
            default => self::EMAIL
        };
    }

    public function getProviderName(): string
    {
        return $this->value;
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::EMAIL => 'Email',
            self::GOOGLE => 'Google',
            self::FACEBOOK => 'Facebook',
            self::APPLE => 'Apple',
        };
    }
}
