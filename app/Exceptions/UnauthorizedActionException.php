<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedActionException extends Exception
{
    protected $message = 'You are not allowed to perform this action.';
    protected $code = 403;
}
