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
            if ($keyIds === [] || $current->getRawOriginal('devolution') !== null || array_diff($keyIds, $pendingIds)) {
                throw ValidationException::withMessages(['keys' => 'As chaves selecionadas não estão pendentes neste empréstimo. Atualize a página.']);
            }

            $received = Received::create(['receiver' => $returnedBy, 'user_id' => $user->id, 'borrow_id' => $current->id]);
            $received->keys()->sync($keyIds);
            if (array_diff($pendingIds, $keyIds) === []) {
                $current->update(['devolution' => now(), 'received_by' => $user->id, 'returned_by' => $returnedBy]);
            }
            activity()->by($user)->on($received)->withProperties(['key_ids' => $keyIds])->log('keys_returned');

            return $received;
        });
    }
}
