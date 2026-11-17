<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const TYPE_DINE_IN = 'DINE_IN';

    public const TYPE_TAKEAWAY = 'TAKEAWAY';

    public const STATUS_OPEN = 'OPEN';

    public const STATUS_AWAITING_PAYMENT = 'AWAITING_PAYMENT';

    public const STATUS_PAID = 'PAID';

    public const STATUS_VOIDED = 'VOIDED';

    /**
     * Statuses that occupy a dining table.
     *
     * @var list<string>
     */
    public const ACTIVE_STATUSES = [self::STATUS_OPEN, self::STATUS_AWAITING_PAYMENT];

    /**
     * Statuses whose items may still be changed. PAID is always locked.
     *
     * @var list<string>
     */
    public const EDITABLE_STATUSES = [self::STATUS_OPEN, self::STATUS_AWAITING_PAYMENT];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_number',
        'order_type',
        'dining_table_id',
        'table_label_snapshot',
        'cashier_id',
        'shift_id',
        'status',
        'subtotal',
        'discount',
        'tax',
        'service_charge',
        'total',
        'notes',
        'paid_at',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount' => 'integer',
            'tax' => 'integer',
            'service_charge' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DiningTable, $this>
     */
    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Shift aktif kasir saat order dibuat. Nullable karena kebijakan wajib shift
     * masih menunggu konfirmasi pemilik (BUSINESS-RULES §11).
     *
     * @return BelongsTo<Shift, $this>
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Restrict a query to orders still occupying a table.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    /**
     * Restrict a query to orders whose items may be edited.
     *
     * @param  Builder<Order>  $query
     */
    public function scopeEditable(Builder $query): Builder
    {
        return $query->whereIn('status', self::EDITABLE_STATUSES);
    }
}
