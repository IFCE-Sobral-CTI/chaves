<?php

namespace App\Console\Commands;

use App\Actions\Borrows\BorrowState;
use App\Actions\Borrows\BorrowTransaction;
use App\Models\Borrow;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RemoveDuplicateBorrowPivot extends Command
{
    protected $signature = 'borrows:remove-duplicate
        {borrow : ID do empréstimo}
        {table : borrow_key ou key_received}
        {pivot : ID exato do vínculo redundante}
        {--user= : ID do administrador responsável}
        {--reason= : Motivo do reparo}
        {--apply : Aplica o reparo; sem esta opção apenas simula}';

    protected $description = 'Remove um vínculo redundante escolhido explicitamente, preservando outro do mesmo par';

    public function handle(BorrowTransaction $transaction, BorrowState $state): int
    {
        try {
            $data = Validator::make([
                'borrow' => $this->argument('borrow'), 'table' => $this->argument('table'),
                'pivot' => $this->argument('pivot'), 'user' => $this->option('user'),
                'reason' => trim((string) $this->option('reason')),
            ], [
                'borrow' => ['required', 'integer', 'min:1', 'exists:borrows,id'],
                'table' => ['required', 'in:borrow_key,key_received'],
                'pivot' => ['required', 'integer', 'min:1'],
                'user' => ['required', 'integer', 'exists:users,id'],
                'reason' => ['required', 'string'],
            ])->validate();
            $user = User::findOrFail($data['user']);
            if (! $user->isAdmin()) {
                throw ValidationException::withMessages(['user' => 'O reparo exige um administrador responsável.']);
            }

            $result = $transaction->run((int) $data['borrow'], [], function (Borrow $borrow) use ($data, $user, $state): array {
                $table = $data['table'];
                $owner = $table === 'borrow_key' ? 'borrow_id' : 'received_id';
                $links = DB::table($table.' as pivot')->select('pivot.id', 'pivot.key_id', 'pivot.'.$owner);
                if ($table === 'borrow_key') {
                    $links->where('pivot.borrow_id', $borrow->id);
                } else {
                    $links->join('receiveds', 'receiveds.id', '=', 'pivot.received_id')
                        ->where('receiveds.borrow_id', $borrow->id);
                }
                $row = (clone $links)->where('pivot.id', $data['pivot'])->lockForUpdate()->first();
                $result = ['borrow_id' => $borrow->id, 'table' => $table, 'pivot_id' => (int) $data['pivot']];
                if ($row === null) {
                    if (DB::table($table)->where('id', $data['pivot'])->exists()) {
                        throw ValidationException::withMessages(['pivot' => 'O vínculo não pertence ao empréstimo informado.']);
                    }

                    return [...$result, 'status' => 'already_absent'];
                }
                if (! in_array((int) $row->key_id, $state->keyIds($borrow), true)) {
                    throw ValidationException::withMessages(['pivot' => 'A chave do vínculo não pertence ao empréstimo.']);
                }
                $duplicates = (clone $links)->where('pivot.'.$owner, $row->{$owner})
                    ->where('pivot.key_id', $row->key_id)->where('pivot.id', '!=', $row->id)
                    ->lockForUpdate()->first();
                if ($duplicates === null) {
                    throw ValidationException::withMessages(['pivot' => 'Não existe outro vínculo do mesmo par; a remoção alteraria o histórico.']);
                }
                $result = [...$result, 'before' => (array) $row, 'after' => null];
                if (! $this->option('apply')) {
                    return [...$result, 'status' => 'simulated'];
                }

                DB::table($table)->where('id', $row->id)->delete();
                activity()->by($user)->on($borrow)->withProperties([
                    ...$result, 'reason' => $data['reason'],
                ])->log('borrow_duplicate_removed');

                return [...$result, 'status' => 'removed'];
            });
            $this->line(json_encode($result, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(implode(' ', Arr::flatten($exception->errors())));

            return self::FAILURE;
        }
    }
}
