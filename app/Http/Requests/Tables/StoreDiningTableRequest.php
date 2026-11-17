<?php

namespace App\Http\Requests\Tables;

use Illuminate\Validation\Rule;

class StoreDiningTableRequest extends DiningTableRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'table_number' => ['required', 'string', 'max:30', Rule::unique('dining_tables', 'table_number')],
            'label' => ['nullable', 'string', 'max:80'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'table_number.unique' => 'Nomor meja sudah digunakan.',
        ];
    }
}
