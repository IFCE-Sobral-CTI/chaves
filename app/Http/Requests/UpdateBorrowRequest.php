<?php

namespace App\Http\Requests;

use App\Models\Borrow;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBorrowRequest extends FormRequest
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
        $borrow = $this->route('borrow');

        return [
            'devolution' => 'nullable|date',
            'observation' => 'nullable',
            'returned_by' => 'nullable',
            'employee_id' => 'required|exists:employees,id',
            'keys' => 'array',
            'keys.*' => [
                'exists:keys,id',
                function ($attribute, $value, $fail) use ($borrow) {
                    $notReceived = Borrow::KeysNotReceived();

                    if ($borrow) {
                        $ownKeys = $borrow->keys()->pluck('id')->toArray();
                        $notReceived = array_diff($notReceived, $ownKeys);
                    }

                    if (in_array((int) $value, $notReceived)) {
                        $fail("A chave selecionada já está emprestada por outro empréstimo e ainda não foi devolvida.");
                    }
                },
            ],
        ];
    }
}
