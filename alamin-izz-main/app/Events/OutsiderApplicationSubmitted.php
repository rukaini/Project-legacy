<?php

namespace App\Events;

use App\Models\EnrollOutsider;
use Illuminate\Foundation\Events\Dispatchable;

class OutsiderApplicationSubmitted
{
    use Dispatchable;

    public function __construct(public EnrollOutsider $enrollment)
    {
    }
}
