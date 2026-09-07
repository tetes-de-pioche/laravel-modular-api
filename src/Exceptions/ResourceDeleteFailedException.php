<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ResourceDeleteFailedException extends BaseException
{
    protected $code = Response::HTTP_UNPROCESSABLE_ENTITY;

    protected $message = 'Failed to delete Resource.';
}
