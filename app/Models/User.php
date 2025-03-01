<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Get the formatted name attribute.
     * 
     * This accessor automatically converts the stored `name` value into a 
     * headline format (e.g., "john doe" → "John Doe") when accessed.
     * 
     * Caching is enabled using `shouldCache()` to optimize performance 
     * and avoid redundant processing.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function name(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Str::headline($value),
        )->shouldCache();
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

    /**
     * Determine if the user can access a specific tenant.
     *
     * This method checks whether the user's `team_id` matches the given tenant's ID.
     *
     * @param Model $tenant The tenant instance to check.
     * @return bool True if the user can access the tenant, otherwise false.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->team_id === $tenant->id;
    }

    /**
     * Retrieve the list of tenants accessible to the user.
     *
     * Currently, this method returns an empty collection, but it can be extended
     * to return the list of tenants based on user roles or permissions.
     *
     * @param Panel $panel The Filament panel instance.
     * @return array|Collection The list of accessible tenants.
     */
    public function getTenants(Panel $panel): array|Collection
    {
        return [];
    }

    /**
     * Get the default tenant for the user.
     *
     * This method returns the team associated with the user as the default tenant.
     *
     * @param Panel $panel The Filament panel instance.
     * @return Model|null The default tenant (team) or null if not found.
     */
    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->team;
    }

    /**
     * Check if the user is a scoped to tenant.
     *
     * This method verifies if the user has the `SUPER_ADMIN` role or `ADMIN` role.
     *
     * @return bool True if the user is a verified, otherwise false.
     */
    public function isNotScopedToTenant(): bool
    {
        return $this->hasRole([UserRole::SUPER_ADMIN, UserRole::ADMIN]);
    }

    /**
     * Determine if the user can access the given Filament panel.
     *
     * This method checks whether the user has an associated team.
     *
     * @param Panel $panel The Filament panel instance.
     * @return bool True if the user has an associated team, otherwise false.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->team->exists;
    }
}
