<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $employee = $this->route('employee');

        $rules['email'] = ['required', 'email', Rule::unique('employees', 'email')->ignore($employee)];
        $rules['alternative_email'] = ['nullable', 'email', Rule::unique('employees', 'alternative_email')->ignore($employee)];
        $rules['registry'] = ['required', 'digits_between:3,20', Rule::unique('employees', 'registry')->ignore($employee)];

        return $rules;
    }
}
