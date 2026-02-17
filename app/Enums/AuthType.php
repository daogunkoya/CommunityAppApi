<?php

namespace App\Enums;

enum AuthType: int
{
    case EMAIL = 1;
    case GOOGLE = 2;
    case FACEBOOK = 3;
    case APPLE = 4;

    public static function fromValue(int $value): self
    {
        return match($value) {
            1 => self::EMAIL,
            2 => self::GOOGLE,
            3 => self::FACEBOOK,
            4 => self::APPLE,
            default => self::EMAIL // Default to email
        };
    }

    public function getProviderName(): string
    {
        return match($this) {
            self::EMAIL => 'email',
            self::GOOGLE => 'google',
            self::FACEBOOK => 'facebook',
            self::APPLE => 'apple',
        };
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::GOOGLE => 'Google',
            self::FACEBOOK => 'Facebook',
            self::APPLE => 'Apple',
        };
    }
}
