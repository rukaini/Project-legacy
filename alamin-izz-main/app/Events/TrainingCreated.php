<?php

namespace App\Events;

use App\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;

class TrainingCreated
{
    use Dispatchable;

    public function __construct(public Training $training)
    {
    }
}
