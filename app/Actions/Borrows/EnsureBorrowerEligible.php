<?php

namespace App\Actions\Borrows;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnsureBorrowerEligible
{
    public function handle(Employee $employee, array $keyIds, bool $lock = false): void
    {
        if (! in_array((int) $employee->type, [Employee::STUDENT, Employee::EXTERNAL], true)) {
            return;
        }

        $validUntil = $employee->getRawOriginal('valid_until');
        if ($validUntil === null || Carbon::parse($validUntil)->startOfDay()->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['employee_id' => 'O mutuário não possui permissão válida para empréstimos.']);
        }

        $permissions = DB::table('borrowable_keys')->where('employee_id', $employee->id)->whereIn('key_id', $keyIds);
        if ($lock) {
            $permissions->lockForUpdate();
        }

        if ($permissions->pluck('key_id')->unique()->count() !== count($keyIds)) {
            throw ValidationException::withMessages(['keys' => 'O mutuário não possui permissão para todas as chaves selecionadas.']);
        }
    }
}
