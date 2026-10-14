<?php

namespace App\Http\Requests;

class UpdateBorrowRequest extends StoreBorrowRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('borrows.update') ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'returned_by' => 'nullable|string|max:255',
        ]);
    }
}
