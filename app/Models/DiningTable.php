<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiningTable extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'AVAILABLE';

    public const STATUS_OCCUPIED = 'OCCUPIED';

    public const STATUS_AWAITING_PAYMENT = 'AWAITING_PAYMENT';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'table_number',
        'label',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * All orders ever placed on this table, including paid history.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The single active DINE_IN order, if any. Status is derived from this.
     *
     * @return HasOne<Order, $this>
     */
    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)
            ->whereIn('status', Order::ACTIVE_STATUSES)
            ->latest();
    }

    /**
     * Restrict a query to tables that may be used for new orders.
     *
     * @param  Builder<DiningTable>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Display label with a stable fallback for tables without a custom label.
     */
    public function displayLabel(): string
    {
        return $this->label ?: $this->table_number;
    }

    /**
     * BUSINESS-RULES §1: table status is derived from the active order, never
     * stored as a second source of truth.
     */
    public function derivedStatus(): string
    {
        $order = $this->relationLoaded('activeOrder') ? $this->activeOrder : $this->activeOrder()->first();

        return match ($order?->status) {
            Order::STATUS_OPEN => self::STATUS_OCCUPIED,
            Order::STATUS_AWAITING_PAYMENT => self::STATUS_AWAITING_PAYMENT,
            default => self::STATUS_AVAILABLE,
        };
    }
}
