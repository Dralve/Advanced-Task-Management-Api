<?php

namespace App\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
    protected $message = 'User Not Found';
    protected $code = 404;
}
