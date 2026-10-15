<?php

namespace App\Http\Middleware;

use App\Support\BorrowRequestMetrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MeasureBorrowRequests
{
    public function __construct(private BorrowRequestMetrics $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $routeName = $route?->getName();
        // shortcut: GET sampling is fixed at 10%; tune if request volume makes the metrics too sparse or noisy.
        if (! is_string($routeName) || ! str_starts_with($routeName, 'borrows.')
            || ($request->isMethod('GET') && random_int(1, 10) !== 1)) {
            return $next($request);
        }

        $requestId = (string) Str::uuid();
        $start = hrtime(true);
        $this->metrics->start();
        $status = 500;
        $bytes = 0;

        try {
            $response = $next($request);
            $status = $response->getStatusCode();
            $bytes = strlen((string) $response->getContent());
            $response->headers->set('X-Request-ID', $requestId);

            return $response;
        } finally {
            try {
                Log::info('borrow.request_metrics', [
                    'request_id' => $requestId,
                    'route' => $routeName,
                    'method' => $request->method(),
                    'status' => $status,
                    'duration_ms' => round((hrtime(true) - $start) / 1_000_000, 2),
                    'response_bytes' => $bytes,
                    ...$this->metrics->finish(),
                ]);
            } catch (Throwable) {
                // Telemetry failures must not change the result of a borrow operation.
            }
        }
    }
}
