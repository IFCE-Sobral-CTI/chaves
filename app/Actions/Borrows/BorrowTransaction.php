<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\Key;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowTransaction
{
    /**
     * Keys (ascending PK), borrow, then employee/receipts/pivots.
     * The initial key set is only a lock hint; current reads validate it again.
     */
    public function run(?int $borrowId, array $requestedIds, Closure $operation): mixed
    {
        $previousIds = $borrowId === null ? [] : DB::table('borrow_key')
            ->where('borrow_id', $borrowId)->pluck('key_id')->all();
        $lockIds = array_values(array_unique(array_map('intval', array_merge($previousIds, $requestedIds))));
        sort($lockIds, SORT_NUMERIC);

        try {
            return DB::transaction(function () use ($borrowId, $lockIds, $operation) {
                $keys = Key::whereIn('id', $lockIds)->orderBy('id')->lockForUpdate()->get();
                if ($keys->count() !== count($lockIds)) {
                    throw ValidationException::withMessages(['keys' => 'Uma das chaves não está mais disponível no cadastro. Atualize a página.']);
                }

                $borrow = $borrowId === null ? null : Borrow::whereKey($borrowId)->lockForUpdate()->firstOrFail();
                if ($borrow !== null) {
                    $currentIds = app(BorrowState::class)->keyIds($borrow);
                    if (array_diff($currentIds, $lockIds)) {
                        throw ValidationException::withMessages(['keys' => 'O empréstimo foi alterado por outra operação. Atualize a página e tente novamente.']);
                    }
                }

                return $operation($borrow, $keys);
            }, attempts: 3);
        } catch (QueryException $exception) {
            if (in_array((int) ($exception->errorInfo[1] ?? 0), [1205, 1213], true)) {
                throw ValidationException::withMessages(['keys' => 'As chaves estão sendo processadas por outra operação. Tente novamente.']);
            }

            throw $exception;
        }
    }
}
