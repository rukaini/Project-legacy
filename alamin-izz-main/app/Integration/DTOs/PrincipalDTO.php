<?php

namespace App\Integration\DTOs;

use Carbon\Carbon;

readonly class PrincipalDTO
{
    public function __construct(
        public string  $id,
        public string  $name,
        public string  $email,
        public ?string $phoneNumber,
        public ?string $ICNumber,
        public ?string $gender,
        public ?string $address,
        public ?string $race,
        public ?string $martialStatus,
        public ?Carbon $appointedDate,
        public ?int    $serviceDate,
        public ?Carbon $pensionDate,
        public ?int    $latestAge,
        public ?string $schoolID,
        public ?string $schoolName,
    ) {}
}
