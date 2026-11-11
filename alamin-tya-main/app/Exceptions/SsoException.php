<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Failure that is safe to show to the user (message contains no secrets).
 */
class SsoException extends RuntimeException
{
}
