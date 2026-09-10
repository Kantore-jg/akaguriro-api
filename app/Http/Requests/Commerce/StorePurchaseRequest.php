<?php

namespace App\Http\Requests\Commerce;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_id' => ['required', 'integer'],
            'supplier_name' => ['nullable', 'string', 'max:200'],
            'purchase_date' => ['required', 'date'],
            'payment_status' => ['nullable', Rule::in(['paid', 'pending', 'partial'])],
            'paid_from_cash' => ['nullable', 'boolean'],
            'cash_register_id' => ['required_if:paid_from_cash,true', 'nullable', 'integer'],
            'cash_motif' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
