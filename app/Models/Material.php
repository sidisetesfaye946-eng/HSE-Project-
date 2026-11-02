<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'material_name',
        'material_description',
        'quantity',
        'initial_stock',
        'unit_of_measure_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'initial_stock' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Material $material): void {
            if (empty($material->initial_stock)) {
                $material->initial_stock = $material->quantity ?? 0;
            }
        });
    }

    public function recalculateStockPool(?int $newHeadOfficeQuantity = null): int
    {
        $quantity = $newHeadOfficeQuantity !== null ? $newHeadOfficeQuantity : (int) $this->quantity;
        $totalDistributed = (int) $this->assignments()->sum('quantity');
        $this->initial_stock = max(0, $quantity) + $totalDistributed;
        $this->saveQuietly();

        return $this->initial_stock;
    }

    public function getTotalAdditionsAttribute(): int
    {
        return (int) $this->histories()
            ->where('quantity_change', '>', 0)
            ->whereIn('event_type', ['stock_added', 'stock_updated'])
            ->sum('quantity_change');
    }

    public function getPhysicalAvailable(?int $totalDistributed = null): int
    {
        if ($totalDistributed === null) {
            $totalDistributed = (int) $this->assignments()->sum('quantity');
        }

        $initialStock = max(0, (int) $this->initial_stock);
        $totalDistributed = max(0, (int) $totalDistributed);

        $available = $initialStock - $totalDistributed;

        return max(0, $available);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MaterialProjectAssignment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaterialHistory::class)->latest();
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    public function hasAvailableQuantity(int $amount): bool
    {
        return $this->quantity >= $amount;
    }

    public function recordHistory(string $eventType, int $quantityChange, string $description, ?int $createdBy = null)
    {
        return MaterialHistory::record($this, $eventType, $quantityChange, $description, $createdBy);
    }

    public function adjustQuantity(int $amount): void
    {
        $this->quantity = max(0, (int) $this->quantity + $amount);
        $totalDistributed = (int) $this->assignments()->sum('quantity');
        $this->initial_stock = max(0, $this->quantity + $totalDistributed);
        $this->save();
    }

    public function deductStock(int $amount): void
    {
        $this->adjustQuantity(-abs($amount));
    }

    public function deductQuantity(int $amount): void
    {
        $this->decrement('quantity', $amount);
    }

    public function addQuantity(int $amount): void
    {
        $this->adjustQuantity($amount);
    }
}
