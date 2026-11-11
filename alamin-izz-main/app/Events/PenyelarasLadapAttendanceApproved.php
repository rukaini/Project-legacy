<?php

namespace App\Events;

use App\Models\Attendance;
use App\Models\PenyelarasLadap;
use Illuminate\Foundation\Events\Dispatchable;

class PenyelarasLadapAttendanceApproved
{
    use Dispatchable;

    public function __construct(public PenyelarasLadap $session, public Attendance $attendance)
    {
    }
}
