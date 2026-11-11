<?php

namespace App\Events;

use App\Models\PenyelarasLadap;
use Illuminate\Foundation\Events\Dispatchable;

class CertificateUploaded
{
    use Dispatchable;

    public function __construct(public PenyelarasLadap $certificate)
    {
    }
}
