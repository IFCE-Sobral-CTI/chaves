<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'email' => 'required|email|unique:employees,email',
            'alternative_email' => 'nullable|email|unique:employees,alternative_email',
            'tel' => 'nullable|digits_between:8,20',
            'registry' => 'required|digits_between:3,20|unique:employees,registry',
            'observation' => 'nullable|min:3',
            'valid_until' => 'nullable|date',
            'type' => 'required|in:1,2,3,4',
        ];
    }
}
