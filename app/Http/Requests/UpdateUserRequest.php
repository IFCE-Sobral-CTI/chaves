<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'registry' => ['required', 'digits_between:3,11', 'unique:users,registry,'.$this->user->id],
            'email' => ['required', 'email', 'unique:users,email,'.$this->user->id],
            'status' => 'required|in:0,1',
            'permission_id' => 'required|exists:permissions,id',
        ];
    }
}
