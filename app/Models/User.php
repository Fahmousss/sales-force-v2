<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements HasName, FilamentUser, HasTenants, HasDefaultTenant
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'team_id'
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

    /**
     * Get the formatted Filament name.
     *
     * This method converts the model's name into a headline-style string.
     *
     * @return string The formatted Filament name.
     */
    public function getFilamentName(): string
    {
        return Str::headline($this->name);
    }

    /**
     * Get the team that the user belongs to.
     *
     * Defines a many-to-one relationship where each user 
     * belongs to a single team.
     *
     * @return BelongsTo<\App\Models\Team, self>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->team_id === $tenant->id;
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return [];
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->team;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SUPER_ADMIN);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->team->exists;
    }
}
