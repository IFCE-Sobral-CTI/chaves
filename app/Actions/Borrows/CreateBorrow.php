<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;

class CreateBorrow
{
    public function __construct(private BorrowTransaction $transaction, private BorrowState $state, private EnsureBorrowerEligible $eligibility) {}

    public function handle(User $user, array $data): Borrow
    {
        return $this->transaction->run(null, $data['keys'], function () use ($user, $data) {
            $employee = Employee::whereKey($data['employee_id'])->lockForUpdate()->firstOrFail();
            $this->eligibility->handle($employee, $data['keys'], lock: true);
            $this->state->ensureAvailable($data['keys']);

            $borrow = $user->borrows()->create(Arr::only($data, ['employee_id', 'observation']));
            $borrow->keys()->sync($data['keys']);
            activity()->by($user)->on($borrow)->withProperties(['key_ids' => $data['keys']])->log('keys_borrowed');

            return $borrow;
        });
    }
}
