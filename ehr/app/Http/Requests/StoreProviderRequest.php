<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'npi' => ['required', 'string', 'size:10'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'credentials' => ['nullable', 'string', 'max:50'],
            'specialty' => ['nullable', 'string', 'max:100'],
            'taxonomy_code' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
