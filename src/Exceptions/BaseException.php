<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Exception;
use Throwable;

abstract class BaseException extends Exception
{
    protected array $errors = [];

    public function __construct(
        ?string $message = null,
        ?int $code = null,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $this->mapMessage($message),
            $this->mapCode($code),
            $previous
        );
    }

    public function addError(mixed $title, mixed $detail): static
    {
        if ($title) {
            $this->errors[$title] = $detail;
        } else {
            $this->errors[] = $detail;
        }

        return $this;
    }

    public function addErrors(iterable $errors): static
    {
        foreach ($errors as $title => $detail) {
            $this->addError($title, $detail);
        }

        return $this;
    }

    private function mapMessage(?string $message = null): string
    {
        return $message ?? $this->message;
    }

    private function mapCode(?int $code = null): int
    {
        return $code ?? $this->code;
    }

    public function errors(): array
    {
        $errorList = [];

        foreach ($this->errors as $key => $value) {
            $translatedErrorValueList = [];

            if (is_array($value)) {
                foreach ($value as $translationKey) {
                    $translatedErrorValueList[] = __($translationKey);
                }
            } else {
                $translatedErrorValueList[] = __($value);
            }

            $errorList[$key] = $translatedErrorValueList;
        }

        return $errorList;
    }
}
