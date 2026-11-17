<?php

namespace App\Http\Requests\Reports;

use App\Actions\Reports\ResolveDateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

final class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('view-reports');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return list<\Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                try {
                    app(ResolveDateRange::class)->handle($this->input('from'), $this->input('to'));
                } catch (ValidationException $exception) {
                    $validator->errors()->merge($exception->errors());
                }
            },
        ];
    }
}
