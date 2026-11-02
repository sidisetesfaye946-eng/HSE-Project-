<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Employee extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'gender',
        'job_title',
        'project_id',
        'project',
        'material_id',
        'material_type',
        'date',
    ];

    public function projectRelation(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function materialRelation(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function materialAssignments(): HasMany
    {
        return $this->hasMany(MaterialEmployeeAssignment::class);
    }

    /**
     * @return Collection<int, array{material_name: string, project_name: string, quantity: int, date: string}>
     */
    public function getMaterialHistory(): Collection
    {
        $assignments = $this->relationLoaded('materialAssignments')
            ? $this->materialAssignments
            : $this->materialAssignments()->with(['material', 'project'])->latest('assigned_date')->get();

        $history = collect();

        foreach ($assignments as $assignment) {
            $materialName = $assignment->material?->material_name ?? ($this->material_type_display !== '-' ? $this->material_type_display : '—');
            $projectName = $assignment->project?->project_name ?? ($this->project_name_display !== '-' ? $this->project_name_display : '—');
            $quantity = $assignment->quantity ?: 1;
            $date = $assignment->assigned_date
                ? $assignment->assigned_date->format('M d, Y')
                : ($this->date ? Carbon::parse($this->date)->format('M d, Y') : $this->created_at->format('M d, Y'));

            $history->push([
                'material_name' => $materialName,
                'project_name' => $projectName,
                'quantity' => $quantity,
                'date' => $date,
            ]);
        }

        if ($history->isEmpty() && (filled($this->material_id) || filled($this->material_type))) {
            $materialName = $this->material_type_display;
            $projectName = $this->project_name_display;

            if ($materialName !== '-' && $materialName !== '') {
                $dateStr = $this->date ? Carbon::parse($this->date)->format('M d, Y') : $this->created_at->format('M d, Y');
                $history->push([
                    'material_name' => $materialName,
                    'project_name' => $projectName,
                    'quantity' => 1,
                    'date' => $dateStr,
                ]);
            }
        }

        return $history;
    }

    public function fullName(): string
    {
        if (filled($this->name)) {
            return $this->name;
        }

        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getEmployeeNameAttribute(): string
    {
        return $this->fullName();
    }

    public function getProjectNameDisplayAttribute(): string
    {
        if ($this->project_id) {
            $p = $this->relationLoaded('projectRelation') ? $this->getRelation('projectRelation') : Project::find($this->project_id);
            if ($p) {
                return $p->project_name;
            }
        }

        $projectAttr = $this->attributes['project'] ?? null;
        if (filled($projectAttr)) {
            return $projectAttr;
        }

        return '-';
    }

    public function getMaterialTypeDisplayAttribute(): string
    {
        if ($this->material_id) {
            $m = $this->relationLoaded('materialRelation') ? $this->getRelation('materialRelation') : Material::find($this->material_id);
            if ($m) {
                return $m->material_name;
            }
        }

        $matAttr = $this->attributes['material_type'] ?? null;
        if (filled($matAttr)) {
            return $matAttr;
        }

        return '-';
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }
}
