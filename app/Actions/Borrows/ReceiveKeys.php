<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;
use App\Models\Received;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ReceiveKeys
{
    public function __construct(private BorrowTransaction $transaction, private BorrowState $state) {}

    public function handle(Borrow $borrow, User $user, array $keyIds, string $returnedBy): Received
    {
        return $this->transaction->run($borrow->id, $keyIds, function (Borrow $current) use ($user, $keyIds, $returnedBy) {
            $pendingIds = $this->state->pendingIds($current);
            $devolutionBefore = $current->getRawOriginal('devolution');
            $receivedByBefore = $current->getRawOriginal('received_by');
            if ($keyIds === [] || $current->getRawOriginal('devolution') !== null || array_diff($keyIds, $pendingIds)) {
                throw ValidationException::withMessages(['keys' => 'As chaves selecionadas não estão pendentes neste empréstimo. Atualize a página.']);
            }

            $received = Received::create(['receiver' => $returnedBy, 'user_id' => $user->id, 'borrow_id' => $current->id]);
            $received->keys()->sync($keyIds);
            $pendingAfter = array_values(array_diff($pendingIds, $keyIds));
            if ($pendingAfter === []) {
                $current->update(['devolution' => now(), 'received_by' => $user->id, 'returned_by' => $returnedBy]);
            }
            activity()->by($user)->on($received)->withProperties([
                'key_ids' => $keyIds,
                'before' => ['pending_key_ids' => $pendingIds, 'devolution' => $devolutionBefore, 'received_by' => $receivedByBefore],
                'after' => ['pending_key_ids' => $pendingAfter, 'devolution' => $current->getRawOriginal('devolution'), 'received_by' => $current->getRawOriginal('received_by')],
            ])->log('keys_returned');

            return $received;
        });
    }
}
