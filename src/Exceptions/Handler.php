<?php

declare(strict_types=1);

namespace TetesDePioche\LaravelModularApi\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as LaravelExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use LaravelModularApi;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends LaravelExceptionHandler
{
    public const JSON_API_MEDIA_TYPE = 'application/vnd.api+json';

    public function register(): void
    {
        $this->renderable(function (BaseException $e, Request $request) {
            return $this->toResponse($e, $request);
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            return $this->expectsApiResponse($request)
                ? $this->toResponse(new EndpointNotFoundException, $request)
                : null;
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            return $this->expectsApiResponse($request)
                ? $this->toResponse($this->createFromValidationException($e), $request)
                : null;
        });
    }

    private function expectsApiResponse(Request $request): bool
    {
        if ($request->expectsJson()) {
            return true;
        }

        $apiRoutePrefix = LaravelModularApi::apiRoutePrefix();

        return $apiRoutePrefix !== '' && $request->routeIs($apiRoutePrefix . '*');
    }

    private function wantsJsonApi(Request $request): bool
    {
        return str_contains((string) $request->header('Accept'), self::JSON_API_MEDIA_TYPE);
    }

    private function createFromValidationException(ValidationException $e): BaseException
    {
        return (new ValidationFailedException($e->getMessage(), $e->status, $e))
            ->addErrors($e->errors());
    }

    private function toResponse(BaseException $e, Request $request): JsonResponse
    {
        $status = (int) $e->getCode();
        $isJsonApi = $this->wantsJsonApi($request);

        $response = $isJsonApi
            ? ['errors' => $this->toJsonApiErrors($e, $request, $status)]
            : ['message' => $e->getMessage(), 'errors' => $e->errors()];

        if (config('app.debug')) {
            $response += [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        return response()->json(
            $response,
            $status,
            $isJsonApi ? ['Content-Type' => self::JSON_API_MEDIA_TYPE] : []
        );
    }

    /**
     * Build the JSON:API `errors` member: one error object per message.
     *
     * `source` follows the request document shape assembled by BaseRequest:
     * a validation key matching a route parameter is reported as
     * `source.parameter`, every other key as a `/data/attributes` pointer,
     * dots becoming nested segments.
     *
     * @return array<int, array<string, mixed>>
     */
    private function toJsonApiErrors(BaseException $e, Request $request, int $status): array
    {
        $title = Response::$statusTexts[$status] ?? null;
        $errors = $e->errors();

        if (empty($errors)) {
            return [array_filter([
                'status' => (string) $status,
                'title' => $title,
                'detail' => $e->getMessage(),
            ])];
        }

        $routeParameters = $request->route()?->parameterNames() ?? [];
        $errorList = [];

        foreach ($errors as $key => $details) {
            foreach (Arr::wrap($details) as $detail) {
                $errorList[] = array_filter([
                    'status' => (string) $status,
                    'title' => $title,
                    'detail' => $detail,
                    'source' => is_string($key)
                        ? $this->toJsonApiErrorSource($key, $routeParameters)
                        : null,
                ]);
            }
        }

        return $errorList;
    }

    /**
     * @param  array<int, string>  $routeParameters
     * @return array<string, string>
     */
    private function toJsonApiErrorSource(string $key, array $routeParameters): array
    {
        if (in_array($key, $routeParameters, true)) {
            return ['parameter' => $key];
        }

        $pointer = str_starts_with($key, 'data.')
            ? $key
            : 'data.attributes.' . $key;

        return ['pointer' => '/' . str_replace('.', '/', $pointer)];
    }
}
