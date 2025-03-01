<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'key',
        'parent_key',
    ];


    /**
     * Get the users that belong to this team.
     *
     * Defines a one-to-many relationship where a single team 
     * can have multiple users.
     *
     * @return HasMany<\App\Models\User, self>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    /**
     * Get the parent team of this team.
     *
     * This defines a self-referencing one-to-many (parent-child) relationship
     * where each team (except the root) belongs to a parent team.
     *
     * @return BelongsTo<\App\Models\Team, self>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_key', 'key');
    }

    /**
     * Get the child teams of this team.
     *
     * This defines a one-to-many relationship where a team
     * can have multiple child teams.
     *
     * @return HasMany<\App\Models\Team, self>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_key', 'key');
    }

    /**
     * Recursively get all descendant teams.
     *
     * Uses a relationship to retrieve all levels of child teams.
     *
     * @return HasMany<\App\Models\Team, self>
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
}
