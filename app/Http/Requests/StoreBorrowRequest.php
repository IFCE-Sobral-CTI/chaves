<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBorrowRequest extends FormRequest
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
            'devolution' => 'nullable|datetime',
            'observation' => 'nullable',
            'employee_id' => 'required|exists:employees,id',
            'keys' => 'array',
            'keys.*' => 'exists:keys,id',
        ];
    }
}
