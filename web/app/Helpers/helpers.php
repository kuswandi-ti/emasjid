<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('mosque_id')) {
    /**
     * Get the current active mosque ID from the authenticated user.
     *
     * Returns null if no user is authenticated or no active mosque is set.
     */
    function mosque_id(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->active_mosque_id;
    }
}

// ─── API Response Helpers ───────────────────────────────────────────

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

if (! function_exists('api_success')) {
    /**
     * Return a success JSON response.
     */
    function api_success(mixed $data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
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
}

if (! function_exists('api_error')) {
    /**
     * Return an error JSON response.
     */
    function api_error(string $message = 'Error', int $statusCode = 400, mixed $errors = null): JsonResponse
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
}

if (! function_exists('api_paginated')) {
    /**
     * Return a success JSON response with paginated data.
     */
    function api_paginated(LengthAwarePaginator $paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
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
        ], 200);
    }
}

if (! function_exists('api_created')) {
    /**
     * Return a created response.
     */
    function api_created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return api_success($data, $message, 201);
    }
}

if (! function_exists('api_updated')) {
    /**
     * Return an updated response.
     */
    function api_updated(mixed $data = null, string $message = 'Resource updated successfully'): JsonResponse
    {
        return api_success($data, $message, 200);
    }
}

if (! function_exists('api_deleted')) {
    /**
     * Return a deleted response.
     */
    function api_deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return api_success(null, $message, 200);
    }
}

if (! function_exists('api_not_found')) {
    /**
     * Return a not found error response.
     */
    function api_not_found(string $message = 'Resource not found'): JsonResponse
    {
        return api_error($message, 404);
    }
}

if (! function_exists('api_unauthorized')) {
    /**
     * Return an unauthorized error response.
     */
    function api_unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return api_error($message, 401);
    }
}

if (! function_exists('api_forbidden')) {
    /**
     * Return a forbidden error response.
     */
    function api_forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return api_error($message, 403);
    }
}

if (! function_exists('api_validation_error')) {
    /**
     * Return a validation error response.
     */
    function api_validation_error(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return api_error($message, 422, $errors);
    }
}

if (! function_exists('api_server_error')) {
    /**
     * Return a server error response.
     */
    function api_server_error(string $message = 'Internal server error', mixed $errors = null): JsonResponse
    {
        return api_error($message, 500, $errors);
    }
}
