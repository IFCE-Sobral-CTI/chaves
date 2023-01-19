<?php

namespace App\Http\Requests;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:employees,email,'.$this->employee->id,
            'alternative_email' => 'nullable|email|unique:employees,alternative_email,'.$this->employee->id,
            'registry' => 'required|digits_between:3,20|unique:employees,registry,'.$this->employee->id,
            'observation' => 'nullable|min:3',
            'valid_until' => 'nullable|date',
            'type' => 'required|in:1,2,3,4',
        ];
    }
}
