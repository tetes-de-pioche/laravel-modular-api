<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ValidationFailedException extends BaseException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected $message = 'The given data was invalid.';
}
