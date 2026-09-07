<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class FeatureInvalidException extends BaseException
{
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected $message = 'Feature disabled or not configured.';
}
