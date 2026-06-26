<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
            'sso.jwt' => \App\Http\Middleware\SsoJwtMiddleware::class,
        ]);
        $middleware->prependToGroup('api', \App\Http\Middleware\ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $statusCode = 500;
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $statusCode = 404;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'errors' => $e->validator->errors()->all()
                    ], $statusCode);
                }

                $message = $e->getMessage() ?: 'Internal Server Error';
                if ($statusCode === 404 && (!$e->getMessage() || str_contains($e->getMessage(), 'No query results') || str_contains($e->getMessage(), 'The route'))) {
                    $message = 'Resource not found';
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $message,
                    'errors' => null
                ], $statusCode);
            }
        });
    })->create();
