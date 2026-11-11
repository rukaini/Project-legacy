<?php

namespace App\Events;

use App\Models\Training;
use Illuminate\Foundation\Events\Dispatchable;

class TrainingCancelled
{
    use Dispatchable;

    public function __construct(public Training $training)
    {
    }
}
