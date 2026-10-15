<?php

namespace Tests\Feature;

use App\Http\Middleware\MeasureBorrowRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Mockery;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class BorrowRequestMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_metrics_are_private_and_do_not_accumulate_between_requests(): void
    {
        $events = [];
        Log::shouldReceive('info')->twice()->with('borrow.request_metrics', Mockery::type('array'))
            ->andReturnUsing(function (string $message, array $context) use (&$events): void {
                $events[] = $context;
            });
        Route::post('/_test/borrow-metrics', function (Request $request): Response {
            for ($index = 0; $index < $request->integer('queries'); $index++) {
                DB::select('select ? as measured_value', ['Private SQL binding']);
            }

            return response('ok', 201);
        })->middleware(MeasureBorrowRequests::class)->name('borrows.metrics-test');

        $first = $this->postJson('/_test/borrow-metrics?term=Private-search', [
            'queries' => 1, 'returned_by' => 'Private Person',
        ])->assertCreated()->assertContent('ok')->assertHeader('X-Request-ID');
        DB::select('select 1');
        $second = $this->postJson('/_test/borrow-metrics', ['queries' => 2])
            ->assertCreated()->assertHeader('X-Request-ID');

        $this->assertCount(2, $events);
        $this->assertNotSame($first->headers->get('X-Request-ID'), $second->headers->get('X-Request-ID'));
        foreach ($events as $index => $event) {
            $this->assertSame([
                'request_id', 'route', 'method', 'status', 'duration_ms', 'response_bytes', 'query_count', 'query_time_ms',
            ], array_keys($event));
            $this->assertSame('borrows.metrics-test', $event['route']);
            $this->assertSame('POST', $event['method']);
            $this->assertSame(201, $event['status']);
            $this->assertSame(2, $event['response_bytes']);
            $this->assertSame($index + 1, $event['query_count']);
            $this->assertGreaterThanOrEqual(0, $event['duration_ms']);
            $this->assertGreaterThanOrEqual(0, $event['query_time_ms']);
            $this->assertStringNotContainsString('Private', json_encode($event, JSON_THROW_ON_ERROR));
        }
        $this->assertSame($first->headers->get('X-Request-ID'), $events[0]['request_id']);
        $this->assertSame($second->headers->get('X-Request-ID'), $events[1]['request_id']);
    }

    public function test_non_borrow_routes_are_not_measured(): void
    {
        Log::shouldReceive('info')->never();
        Route::post('/_test/other-metrics', fn (): Response => response('ok'))
            ->middleware(MeasureBorrowRequests::class)->name('other.metrics-test');

        $this->postJson('/_test/other-metrics')->assertOk()->assertContent('ok')->assertHeaderMissing('X-Request-ID');
    }

    public function test_logger_failure_does_not_change_the_write_response(): void
    {
        Log::shouldReceive('info')->once()->andThrow(new RuntimeException('Logger unavailable'));
        Route::post('/_test/borrow-metrics', fn (): Response => response('ok', 201))
            ->middleware(MeasureBorrowRequests::class)->name('borrows.metrics-test');

        $this->postJson('/_test/borrow-metrics')->assertCreated()->assertContent('ok')->assertHeader('X-Request-ID');
    }

    public function test_validation_failure_records_the_response_status_without_its_payload(): void
    {
        $events = [];
        Log::shouldReceive('info')->once()->with('borrow.request_metrics', Mockery::type('array'))
            ->andReturnUsing(function (string $message, array $context) use (&$events): void {
                $events[] = $context;
            });
        Route::post('/_test/borrow-metrics', function (): Response {
            throw ValidationException::withMessages(['keys' => 'Private validation message']);
        })->middleware(MeasureBorrowRequests::class)->name('borrows.metrics-test');

        $this->postJson('/_test/borrow-metrics')->assertUnprocessable()->assertJsonValidationErrors('keys');

        $this->assertCount(1, $events);
        $this->assertSame(422, $events[0]['status']);
        $this->assertStringNotContainsString('Private', json_encode($events[0], JSON_THROW_ON_ERROR));
    }
}
