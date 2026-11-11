<?php

namespace App\Events;

use App\Models\EnrollGuruBaru;
use Illuminate\Foundation\Events\Dispatchable;

class GuruBaruApplicationSubmitted
{
    use Dispatchable;

    public function __construct(public EnrollGuruBaru $enrollment)
    {
    }
}
