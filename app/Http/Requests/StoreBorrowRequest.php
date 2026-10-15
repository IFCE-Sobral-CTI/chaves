<?php

namespace App\Http\Requests;

use App\Actions\Borrows\EnsureBorrowerEligible;
use App\Models\Employee;
use App\Models\Key;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
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
            'devolution' => 'prohibited',
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
            $pendingIds = $borrow === null ? $keyIds : array_values(array_diff($keyIds, $borrow->receivedKeys()));
            if (Key::borrowed($borrow?->id)->whereIn('keys.id', $pendingIds)->exists()) {
                $validator->errors()->add('keys', 'Alguma das chaves selecionadas já está emprestada e ainda não foi devolvida.');
            }

            $employee = Employee::findOrFail($this->input('employee_id'));
            try {
                app(EnsureBorrowerEligible::class)->handle($employee, $keyIds);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        }];
    }
}
