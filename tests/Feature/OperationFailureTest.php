<?php

namespace Tests\Feature;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class OperationFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_operation_failure_reports_once_and_redirects_to_the_requested_detail(): void
    {
        Exceptions::fake();
        Route::get('/test-operation-failure/{permission}', [OperationFailureRouteController::class, 'fail']);

        $this->get('/test-operation-failure/42')
            ->assertRedirect(route('permissions.show', ['permission' => 42]))
            ->assertSessionHas('flash', [
                'status' => 'danger',
                'message' => 'Ocorreu um erro ao processar a solicitação.',
            ]);

        Exceptions::assertReportedCount(1);
    }
}

class OperationFailureRouteController extends Controller
{
    public function fail(int $permission): RedirectResponse
    {
        return $this->operationFailed(
            new RuntimeException('Injected operation failure.'),
            'permissions.show',
            ['permission' => $permission],
        );
    }
}
