<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function deletionFailed(Exception $exception, string $route): RedirectResponse
    {
        if ($exception instanceof QueryException && (int) ($exception->errorInfo[1] ?? 0) === 1451) {
            return to_route($route)->with('flash', [
                'status' => 'danger',
                'message' => 'Não é possível excluir este registro porque ele possui histórico de empréstimos ou devoluções.',
            ]);
        }

        report($exception);

        return to_route($route)->with('flash', ['status' => 'danger', 'message' => 'Ocorreu um erro ao processar a solicitação.']);
    }
}
