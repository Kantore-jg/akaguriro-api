<?php

namespace App\Http\Requests\PaymentMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_receipts') ?? false;
    }

    public function rules(): array
    {
        return [
            'market_id' => ['nullable', 'exists:markets,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', Rule::in(['bank', 'mobile_money', 'cash'])],
            'account_number' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:150'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
