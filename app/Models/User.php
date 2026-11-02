<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'project_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'site_officer_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function receivedAssignments(): HasMany
    {
        return $this->hasMany(MaterialProjectAssignment::class, 'receiver_id');
    }

    public function getAssignedProjectAttribute(): ?Project
    {
        if (isset($this->attributes['project_id']) && $this->attributes['project_id']) {
            $project = Project::find($this->attributes['project_id']);
            if ($project) {
                return $project;
            }
        }

        return $this->managedProjects()->first() ?? $this->projects()->first();
    }

    public function getProjectIdAttribute(): ?int
    {
        return $this->attributes['project_id'] ?? $this->assignedProject?->id;
    }

    public function getAssignedProjectIdAttribute(): ?int
    {
        return $this->assignedProject?->id;
    }

    public function getAssignedSiteNameAttribute(): ?string
    {
        return $this->assignedProject?->project_name;
    }
}
