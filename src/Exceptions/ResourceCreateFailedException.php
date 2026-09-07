<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ResourceCreateFailedException extends BaseException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected $message = 'Failed to create Resource.';
}
