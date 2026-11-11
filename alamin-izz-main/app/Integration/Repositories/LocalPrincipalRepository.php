<?php

namespace App\Integration\Repositories;

use App\Integration\Contracts\PrincipalRepositoryInterface;
use App\Integration\DTOs\PrincipalDTO;
use App\Models\Principal;

class LocalPrincipalRepository implements PrincipalRepositoryInterface
{
    public function findByEmail(string $email): ?PrincipalDTO
    {
        $principal = Principal::with('school')->where('email', $email)->first();

        return $principal ? $this->toDTO($principal) : null;
    }

    public function updateProfile(string $email, array $data): void
    {
        Principal::where('email', $email)->update($data);
    }

    private function toDTO(Principal $principal): PrincipalDTO
    {
        return new PrincipalDTO(
            id:            $principal->principalID,
            name:          $principal->principalName,
            email:         $principal->email,
            phoneNumber:   $principal->phoneNumber,
            ICNumber:      $principal->ICNumber,
            gender:        $principal->gender,
            address:       $principal->address,
            race:          $principal->race,
            martialStatus: $principal->martialStatus,
            appointedDate: $principal->appointedDate,
            serviceDate:   $principal->serviceDate,
            pensionDate:   $principal->pensionDate,
            latestAge:     $principal->latestAge,
            schoolID:      $principal->schoolID,
            schoolName:    $principal->school?->schoolName,
        );
    }
}
