<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\Received;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DeleteReceived
{
    public function __construct(private BorrowTransaction $transaction, private BorrowState $state) {}

    public function handle(Borrow $borrow, Received $received, User $user): void
    {
        $this->transaction->run($borrow->id, [], function (Borrow $current) use ($received, $user) {
            $receipt = $current->received()->whereKey($received->id)->lockForUpdate()->first();
            if ($receipt === null) {
                throw ValidationException::withMessages(['keys' => 'Esta devolução não pertence ao empréstimo ou já foi excluída.']);
            }

            $receipt->delete();
            $pendingIds = $this->state->pendingIds($current);
            $this->state->ensureAvailable($pendingIds, $current->id);
            if ($pendingIds !== []) {
                $current->update(['devolution' => null, 'received_by' => null, 'returned_by' => null]);
            }
            activity()->by($user)->on($current)->withProperties(['received_id' => $received->id, 'pending_key_ids' => $pendingIds])->log('return_deleted');
        });
    }
}
