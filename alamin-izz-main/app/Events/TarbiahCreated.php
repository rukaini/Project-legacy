<?php

namespace App\Events;

use App\Models\Tarbiah;
use Illuminate\Foundation\Events\Dispatchable;

class TarbiahCreated
{
    use Dispatchable;

    public function __construct(public Tarbiah $tarbiah)
    {
    }
}
