<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** @deprecated No longer used — attendance is now marked via the session's scheduled time window */
class AttendanceSessionCreated
{
    use Dispatchable;
}
