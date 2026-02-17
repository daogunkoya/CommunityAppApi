<?php

namespace App\Enums;

enum SkillLevel: int
{
    case Beginner = 1;
    case Intermediate = 2;
    case Advanced = 3;

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Beginner',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
        };
    }

    public function isAtLeast(self $required): bool
    {
        return $this->value >= $required->value;
    }

    public function isLowerThan(self $other): bool
    {
        return $this->value < $other->value;
    }
}
