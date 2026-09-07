<?php

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class LocalizationLocaleUnsupportedException extends BaseException
{
    protected $code = Response::HTTP_NOT_ACCEPTABLE;

    protected $message = 'Unsupported language.';
}
