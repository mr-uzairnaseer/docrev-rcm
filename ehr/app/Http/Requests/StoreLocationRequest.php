<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:20'],
            'place_of_service_code' => ['nullable', 'string', 'max:5'],
        ];
    }
}
