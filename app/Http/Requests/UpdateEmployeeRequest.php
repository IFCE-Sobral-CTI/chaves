<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! in_array((int) $this->input('type'), [Employee::STUDENT, Employee::EXTERNAL], true)) {
            $this->merge(['borrowable_key_ids' => []]);
        }

        if (is_string($this->input('tel'))) {
            $tel = trim($this->input('tel'));
            $digits = preg_replace('/\D/', '', $tel);
            $this->merge(['tel' => $tel === '' ? null : ($digits === '' ? $tel : $digits)]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'usual_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'email' => 'required|email|unique:employees,email,'.$this->employee->id,
            'alternative_email' => 'nullable|email|unique:employees,alternative_email,'.$this->employee->id,
            'tel' => 'nullable|digits_between:8,20',
            'registry' => 'required|digits_between:3,20|unique:employees,registry,'.$this->employee->id,
            'observation' => 'nullable|min:3',
            'valid_until' => 'nullable|date',
            'type' => 'required|in:1,2,3,4',
            'borrowable_key_ids' => ['present', 'array'],
            'borrowable_key_ids.*' => ['integer', 'distinct', 'exists:keys,id'],
        ];
    }
}
