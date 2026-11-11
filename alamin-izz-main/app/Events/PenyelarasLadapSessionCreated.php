<?php

namespace App\Events;

use App\Models\PenyelarasLadap;
use Illuminate\Foundation\Events\Dispatchable;

class PenyelarasLadapSessionCreated
{
    use Dispatchable;

    public function __construct(public PenyelarasLadap $session)
    {
    }
}
