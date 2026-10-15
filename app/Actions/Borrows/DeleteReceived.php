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

            $pendingBefore = $this->state->pendingIds($current);
            $devolutionBefore = $current->getRawOriginal('devolution');
            $receivedByBefore = $current->getRawOriginal('received_by');
            $receipt->delete();
            $pendingIds = $this->state->pendingIds($current);
            $this->state->ensureAvailable($pendingIds, $current->id);
            if ($pendingIds !== []) {
                $current->update(['devolution' => null, 'received_by' => null, 'returned_by' => null]);
            }
            activity()->by($user)->on($current)->withProperties([
                'received_id' => $received->id,
                'before' => ['pending_key_ids' => $pendingBefore, 'devolution' => $devolutionBefore, 'received_by' => $receivedByBefore],
                'after' => ['pending_key_ids' => $pendingIds, 'devolution' => $current->getRawOriginal('devolution'), 'received_by' => $current->getRawOriginal('received_by')],
            ])->log('return_deleted');
        });
    }
}
