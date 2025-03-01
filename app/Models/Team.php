<?php

namespace App\Models;


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
        'type',
        'key',
        'parent_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => \App\Enums\TeamType::class,
        ];
    }


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

    /**
     * Get all descendant teams of the current team.
     *
     * This method retrieves all child teams recursively.
     *
     * @return \Illuminate\Support\Collection A collection of descendant teams.
     */
    public function getDescendants()
    {
        return $this->getAllDescendants($this);
    }

    /**
     * Recursively retrieve all descendant teams of a given team.
     *
     * This method traverses the hierarchy and collects all child teams.
     *
     * @param self $team The team instance whose descendants are to be retrieved.
     * @return \Illuminate\Support\Collection A collection of all descendant teams.
     */
    public function getAllDescendants($team)
    {
        return $team->children->flatMap(fn($child) => collect([$child])->merge($this->getAllDescendants($child)));
    }

    /**
     * Check if the current team is a descendant of a given ancestor team.
     *
     * This method verifies whether the current team exists in the 
     * hierarchy of the specified ancestor team.
     *
     * @param self $ancestor The ancestor team to check against.
     * @return bool True if the current team is a descendant, otherwise false.
     */
    public function isDescendantOf($ancestor)
    {
        return $this->getAllDescendants($ancestor)->contains('id', $this->id);
    }
}
