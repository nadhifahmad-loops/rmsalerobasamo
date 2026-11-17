<?php

namespace App\Http\Requests\Payments;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayOrderRequest extends FormRequest
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
            'payment_method' => ['required', 'string', Rule::in(Payment::METHODS)],
            'idempotency_key' => [
                'required',
                'string',
                'min:16',
                'max:'.config('pos.payments.idempotency_key_max_length', 100),
                'regex:/^[A-Za-z0-9_-]+$/',
            ],
            'received_amount' => [
                'nullable',
                'integer',
                'min:0',
                'max:'.config('pos.payments.max_received_amount', 1_000_000_000_000),
                'required_if:payment_method,'.Payment::METHOD_CASH,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak dikenal.',
            'idempotency_key.required' => 'Kunci permintaan pembayaran tidak ditemukan. Muat ulang halaman checkout.',
            'idempotency_key.regex' => 'Kunci permintaan pembayaran tidak valid.',
            'received_amount.required_if' => 'Nominal uang diterima wajib diisi untuk pembayaran tunai.',
            'received_amount.min' => 'Nominal uang diterima tidak boleh negatif.',
            'received_amount.max' => 'Nominal uang diterima melebihi batas yang diizinkan.',
        ];
    }
}
