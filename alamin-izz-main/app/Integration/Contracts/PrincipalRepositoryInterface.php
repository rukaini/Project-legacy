<?php

namespace App\Integration\Contracts;

use App\Integration\DTOs\PrincipalDTO;

interface PrincipalRepositoryInterface
{
    public function findByEmail(string $email): ?PrincipalDTO;

    public function updateProfile(string $email, array $data): void;
}
