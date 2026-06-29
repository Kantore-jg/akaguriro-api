<?php

namespace App\Http\Requests\Concerns;

trait NormalizesUserPhone
{
    protected function normalizePhoneInput(): void
    {
        $phone = $this->input('phone');

        if ($phone === null || trim((string) $phone) === '') {
            $this->merge(['phone' => null]);

            return;
        }

        $this->merge(['phone' => preg_replace('/\s+/', '', trim((string) $phone))]);
    }
}