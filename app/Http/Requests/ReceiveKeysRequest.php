<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveKeysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.receive', $this->route('borrow')) ?? false;
    }

    public function rules(): array
    {
        return [
            'returned_by' => 'required|string|min:2|max:255',
            'keys' => 'bail|required|array|list|min:1',
            'keys.*' => 'bail|required|integer|min:1|distinct',
        ];
    }

    public function attributes(): array
    {
        return ['returned_by' => 'devolução', 'keys' => 'chaves', 'keys.*' => 'chave'];
    }
}
