<?php

namespace App\Http\Requests\Commerce;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_session_id' => ['required', 'integer'],
            'type' => ['required', Rule::in(['CASH_IN', 'CASH_OUT', 'EXPENSE', 'WITHDRAWAL'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'motif' => ['nullable', 'string', 'max:255'],
        ];
    }
}
