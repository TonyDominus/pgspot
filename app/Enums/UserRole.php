<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'superadmin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Utente',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function isAtLeast(self $role): bool
    {
        $hierarchy = [
            self::User->value => 0,
            self::Admin->value => 1,
            self::SuperAdmin->value => 2,
        ];

        return $hierarchy[$this->value] >= $hierarchy[$role->value];
    }
}
