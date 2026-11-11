<?php

namespace App\Enums;

enum Role: string
{
    case GuruBesar  = 'guru_besar';
    case Teacher    = 'teacher';
    case Outsider   = 'outsider';
    case GuruBaru   = 'guru_baru';
    case HR         = 'hr';
}
