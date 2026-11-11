<?php

namespace App\Integration\Contracts;

use App\Integration\DTOs\TeacherDTO;
use Illuminate\Support\Collection;

interface TeacherRepositoryInterface
{
    public function findById(int|string $id): ?TeacherDTO;

    public function findByEmail(string $email): ?TeacherDTO;

    public function all(): Collection;

    public function allForDropdown(): Collection;

    public function updateProfile(string $email, array $data): void;
}
