<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\NormalizesUserPhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use NormalizesUserPhone;

    public function authorize(): bool
    {
        return $this->user()?->can('manage_users') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizePhoneInput();
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)->whereNotNull('phone')],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'string', Rule::in(array_column(UserRole::cases(), 'value'))],
            'is_active' => ['nullable', 'boolean'],
            'managed_market_id' => ['nullable', 'integer', 'exists:markets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre compte.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
        ];
    }
}