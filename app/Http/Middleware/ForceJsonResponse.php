<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('api/*') || $request->is('v1/*') || $request->is('guests*')) {
            $request->headers->set('Accept', 'application/json');
            
            // If there is a body, ensure Content-Type is application/json so Laravel parses JSON
            if (in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH']) && !$request->isJson()) {
                $request->headers->set('Content-Type', 'application/json');
            }
        }

        $response = $next($request);

        if ($request->is('api/*') || $request->is('v1/*') || $request->is('guests*') || $response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('Content-Type', 'application/json; charset=utf-8', true);
        }

        return $response;
    }
}
