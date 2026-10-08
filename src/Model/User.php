<?php

declare(strict_types=1);

namespace App\Model;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $name,
        public readonly string $role,
        public readonly string $roleName
    ) {
    }
}
