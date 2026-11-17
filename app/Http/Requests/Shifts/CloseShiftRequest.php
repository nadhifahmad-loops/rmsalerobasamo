<?php

namespace App\Http\Requests\Shifts;

use App\Models\Shift;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

final class CloseShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $shift = $this->route('shift');

        if ($user === null || ! $shift instanceof Shift) {
            return false;
        }

        return $shift->cashier_id === $user->id || Gate::forUser($user)->allows('manage-shifts');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_cash' => [
                'required',
                'integer',
                'min:0',
                'max:'.config('pos.shifts.max_actual_cash', 1_000_000_000_000),
            ],
        ];
    }
}
