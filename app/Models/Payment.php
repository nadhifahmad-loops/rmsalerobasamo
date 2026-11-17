<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'CASH';

    public const METHOD_QRIS = 'QRIS';

    public const METHOD_TRANSFER = 'TRANSFER';

    public const METHOD_DEBIT = 'DEBIT';

    public const STATUS_PAID = 'PAID';

    /**
     * @var list<string>
     */
    public const METHODS = [
        self::METHOD_CASH,
        self::METHOD_QRIS,
        self::METHOD_TRANSFER,
        self::METHOD_DEBIT,
    ];

    /**
     * @var array<string, string>
     */
    public const METHOD_LABELS = [
        self::METHOD_CASH => 'Tunai',
        self::METHOD_QRIS => 'QRIS',
        self::METHOD_TRANSFER => 'Transfer',
        self::METHOD_DEBIT => 'Debit',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'payment_number',
        'idempotency_key',
        'payment_method',
        'amount',
        'received_amount',
        'change_amount',
        'status',
        'processed_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'received_amount' => 'integer',
            'change_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
