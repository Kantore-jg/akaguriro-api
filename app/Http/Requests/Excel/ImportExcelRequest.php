<?php

namespace App\Http\Requests\Excel;

use Illuminate\Foundation\Http\FormRequest;

class ImportExcelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier Excel est obligatoire.',
            'file.mimes' => 'Le fichier doit être au format XLSX ou XLS.',
        ];
    }
}
