<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaterialRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'material_id',
        'project_id',
        'requested_by',
        'quantity',
        'permitted_quantity',
        'description',
        'employee_file',
        'status',
        'approved_by',
        'approved_at',
        'distributed_at',
        'rejection_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'permitted_quantity' => 'integer',
            'approved_at' => 'datetime',
            'distributed_at' => 'datetime',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function getMaterialNameAttribute(): string
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return $this->items->map(fn (MaterialRequestItem $item): string => $item->material?->material_name ?? 'Unknown')->join(', ');
        }

        if ($this->items()->exists()) {
            return $this->items()->with('material')->get()->map(fn (MaterialRequestItem $item): string => $item->material?->material_name ?? 'Unknown')->join(', ');
        }

        return $this->material?->material_name ?? 'N/A';
    }

    public function getTotalQuantityAttribute(): int
    {
        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return (int) $this->items->sum('quantity');
        }

        return (int) ($this->quantity ?? 0);
    }

    public function requestedEmployees(): HasMany
    {
        return $this->hasMany(MaterialRequestEmployee::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPartiallyApproved(): bool
    {
        return $this->status === 'partial_approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isDistributed(): bool
    {
        return $this->distributed_at !== null;
    }
}
