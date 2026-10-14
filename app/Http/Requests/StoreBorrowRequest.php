<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Key;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBorrowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'devolution' => 'nullable|date',
            'observation' => 'nullable|string',
            'employee_id' => 'required|integer|exists:employees,id',
            'keys' => 'bail|required|array|min:1|exists:keys,id',
            'keys.*' => 'required|integer|distinct',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $keyIds = $this->input('keys');
            $borrow = $this->route('borrow');
            if (Key::borrowed($borrow?->id)->whereIn('keys.id', $keyIds)->exists()) {
                $validator->errors()->add('keys', 'Alguma das chaves selecionadas já está emprestada e ainda não foi devolvida.');
            }

            $employee = Employee::findOrFail($this->input('employee_id'));
            if (in_array((int) $employee->type, [Employee::STUDENT, Employee::EXTERNAL], true)) {
                $validUntil = $employee->getRawOriginal('valid_until');
                if ($validUntil === null || Carbon::parse($validUntil)->isPast()) {
                    $validator->errors()->add('employee_id', 'O mutuário não possui permissão válida para empréstimos.');

                    return;
                }

                $allowedCount = $employee->borrowable_keys()->whereIn('keys.id', $keyIds)->distinct()->count('keys.id');
                if ($allowedCount !== count($keyIds)) {
                    $validator->errors()->add('keys', 'O mutuário não possui permissão para todas as chaves selecionadas.');
                }
            }
        }];
    }
}
