<?php

namespace App\Actions\Borrows;

use App\Models\Borrow;

class DeleteBorrow
{
    public function __construct(private BorrowTransaction $transaction) {}

    public function handle(Borrow $borrow): void
    {
        $this->transaction->run($borrow->id, [], fn (Borrow $current) => $current->delete());
    }
}
