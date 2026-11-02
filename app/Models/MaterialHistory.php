<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialHistory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'material_id',
        'event_type',
        'quantity_change',
        'balance_before',
        'balance_after',
        'description',
        'created_by',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function record(Material $material, string $eventType, int $quantityChange, string $description, ?int $createdBy = null): self
    {
        $material = $material->fresh();

        return self::create([
            'material_id' => $material->id,
            'event_type' => $eventType,
            'quantity_change' => $quantityChange,
            'balance_before' => $material->quantity - $quantityChange,
            'balance_after' => $material->quantity,
            'description' => $description,
            'created_by' => $createdBy,
        ]);
    }
}
