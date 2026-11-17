<?php

namespace App\Http\Requests\Tables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Mutasi meja dibatasi oleh gate yang sama dengan route middleware.
 */
abstract class DiningTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-tables');
    }
}
