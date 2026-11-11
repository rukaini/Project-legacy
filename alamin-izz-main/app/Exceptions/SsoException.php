<?php

namespace App\Exceptions;

use RuntimeException;

/** A failure whose message is safe to show to the end user. */
class SsoException extends RuntimeException
{
}
