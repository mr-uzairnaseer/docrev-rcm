<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportEraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'edi_835' => ['required', 'string', 'min:20'],
            'trace_number' => ['nullable', 'string', 'max:50'],
        ];
    }
}
