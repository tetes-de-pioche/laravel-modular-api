<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ObfuscatedIdInvalidException extends BaseException
{
    protected $code = Response::HTTP_BAD_REQUEST;

    protected $message = 'ID input is incorrect.';
}
