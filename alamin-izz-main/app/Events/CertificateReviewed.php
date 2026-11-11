<?php

namespace App\Events;

use App\Models\PenyelarasLadap;
use Illuminate\Foundation\Events\Dispatchable;

class CertificateReviewed
{
    use Dispatchable;

    public function __construct(public PenyelarasLadap $certificate)
    {
    }
}
