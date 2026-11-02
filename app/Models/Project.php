<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_name',
        'project_code',
        'site_officer_id',
    ];

    public function siteOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'site_officer_id');
    }

    public function getAssignedSiteOfficerAttribute(): ?User
    {
        if ($this->siteOfficer) {
            return $this->siteOfficer;
        }

        return User::role('HSE Site Officer')
            ->where(function ($query) {
                $query->where('project_id', $this->id)
                    ->orWhereHas('projects', fn ($q) => $q->where('projects.id', $this->id));
            })
            ->first();
    }

    public function materialAssignments(): HasMany
    {
        return $this->hasMany(MaterialProjectAssignment::class);
    }

    public function employeeMaterialAssignments(): HasMany
    {
        return $this->hasMany(MaterialEmployeeAssignment::class);
    }

    public function isManagedBy(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'HSE Officer'])
            || $this->site_officer_id === $user->id
            || $user->project_id === $this->id
            || $user->projects()->where('projects.id', $this->id)->exists();
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)->withTimestamps();
    }
}
