<?php

namespace App\Http\Requests\Shifts;

use Illuminate\Foundation\Http\FormRequest;

final class OpenShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'opening_cash' => [
                'required',
                'integer',
                'min:0',
                'max:'.config('pos.shifts.max_opening_cash', 1_000_000_000),
            ],
        ];
    }
}
