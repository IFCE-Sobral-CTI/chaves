<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UpdateBorrow
{
    public function __construct(private BorrowTransaction $transaction, private BorrowState $state, private EnsureBorrowerEligible $eligibility) {}

    public function handle(Borrow $borrow, User $user, array $data): void
    {
        $this->transaction->run($borrow->id, $data['keys'], function (Borrow $current) use ($user, $data) {
            $oldIds = $this->state->keyIds($current);
            $receivedIds = $this->state->receivedIds($current);
            $newIds = array_map('intval', $data['keys']);
            if (array_diff($receivedIds, $newIds)) {
                throw ValidationException::withMessages(['keys' => 'Não é possível remover chaves que possuem devolução registrada.']);
            }
            if ($current->getRawOriginal('devolution') === null && array_diff($newIds, $receivedIds) === []) {
                throw ValidationException::withMessages(['keys' => 'Registre a devolução das chaves pendentes antes de encerrar o empréstimo.']);
            }
            if ($current->getRawOriginal('devolution') !== null && (array_diff($newIds, $oldIds) || array_diff($oldIds, $newIds))) {
                throw ValidationException::withMessages(['keys' => 'Não é possível alterar as chaves de um empréstimo encerrado.']);
            }
            $employee = Employee::whereKey($data['employee_id'])->lockForUpdate()->firstOrFail();
            $this->eligibility->handle($employee, $newIds, lock: true);
            $this->state->ensureAvailable(array_values(array_diff($newIds, $receivedIds)), $current->id);
            $current->update(Arr::only($data, ['employee_id', 'observation']));
            $current->keys()->sync($newIds);
            if (array_diff($oldIds, $newIds) || array_diff($newIds, $oldIds)) {
                activity()->by($user)->on($current)->withProperties(['old_key_ids' => $oldIds, 'key_ids' => $newIds])->log('keys_updated');
            }
        });
    }
}
