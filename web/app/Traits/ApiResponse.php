<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a success JSON response with resource.
     *
     * @param  JsonResource  $resource
     * @param  string  $message
     * @param  int  $statusCode
     * @return JsonResponse
     */
    protected function successWithResource(JsonResource $resource, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource,
        ], $statusCode);
    }

    /**
     * Return a success JSON response with paginated data.
     *
     * @param  LengthAwarePaginator|ResourceCollection  $paginator
     * @param  string  $message
     * @return JsonResponse
     */
    protected function successWithPagination(LengthAwarePaginator|ResourceCollection $paginator, string $message = 'Success'): JsonResponse
    {
        if ($paginator instanceof ResourceCollection) {
            $data = $paginator->response()->getData(true);
        } else {
            $data = [
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'path' => $paginator->path(),
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data['data'] ?? $data,
            'pagination' => $data['pagination'] ?? $data['meta'] ?? null,
        ], 200);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @param  mixed  $errors
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', int $statusCode = 400, mixed $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error JSON response.
     *
     * @param  mixed  $errors
     * @param  string  $message
     * @return JsonResponse
     */
    protected function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }

    /**
     * Return a not found error JSON response.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, 404);
    }

    /**
     * Return an unauthorized error JSON response.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, 401);
    }

    /**
     * Return a forbidden error JSON response.
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, 403);
    }

    /**
     * Return a server error JSON response.
     *
     * @param  string  $message
     * @param  mixed  $errors
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal server error', mixed $errors = null): JsonResponse
    {
        return $this->error($message, 500, $errors);
    }

    /**
     * Return a created response (for POST requests).
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return JsonResponse
     */
    protected function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Return a no content response (for DELETE requests).
     *
     * @param  string  $message
     * @return JsonResponse
     */
    protected function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->success(null, $message, 200);
    }

    /**
     * Return an updated response (for PUT/PATCH requests).
     *
     * @param  mixed  $data
     * @param  string  $message
     * @return JsonResponse
     */
    protected function updated(mixed $data = null, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->success($data, $message, 200);
    }
}
