<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\User;

class DeleteBorrow
{
    public function __construct(private BorrowTransaction $transaction) {}

    public function handle(Borrow $borrow, ?User $user = null): void
    {
        $this->transaction->run($borrow->id, [], function (Borrow $current) use ($user) {
            $state = app(BorrowState::class);
            $keyIds = $state->keyIds($current);
            $before = [
                'employee_id' => (int) $current->employee_id,
                'key_ids' => $keyIds,
                'pending_key_ids' => array_values(array_diff($keyIds, $state->receivedIds($current))),
                'devolution' => $current->getRawOriginal('devolution'),
            ];
            $activity = activity()->on($current)->withProperties(['before' => $before, 'after' => null]);
            if ($user) {
                $activity->by($user);
            }
            $activity->log('borrow_deleted');
            $current->delete();
        });
    }
}
