<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $owner_type
 * @property string $owner_id
 * @property string $kind
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $visibility
 * @property bool $requires_approval
 * @property int|null $position
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Space|null $parent
 * @property-read \App\Models\Profile|\App\Models\Space|null $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Space> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $moder
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pins
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pins
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $media
 */
#[Table('spaces')]
#[Fillable(['parent_id', 'owner_type', 'owner_id', 'kind', 'name', 'slug', 'description', 'visibility', 'requires_approval', 'position', 'settings'])]
class Space extends Model
{
    use HasUuids, SoftDeletes;
    
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'requires_approval' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
     * 
     * @return void
     */
    protected static function booted(): void
    {
        // soft deletes are an UPDATE, so the database cascades never fire - we have to do it here.
        static::deleted(function (Space $space) {
            if ($space->isForceDeleting()) {
                return;
            }

            $space->forceFill(['slug' => $space->slug.'#deleted-'.$space->id])->saveQuietly();

            $space->children()->lazyById()->each->delete();
            $space->posts()->lazyById()->each->delete();
            $space->media()->lazyById()->each->delete();
        });
    }

    /**
     * Get the owner of this space (Profile or Space).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\App\Models\Profile|\App\Models\Space, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the parent space of this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Space, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Space::class, 'parent_id');
    }

    /**
     * Get the child spaces of this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Space>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Space::class, 'parent_id')->orderBy('position');
    }

    /**
     * Get the posts in this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the root posts in this space (not replies).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post>
     */
    public function roots(): HasMany
    {
        return $this->posts()->whereNull('root_id');
    }

    /**
     * Get the members of this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'space_members')
            ->using(SpaceMember::class)
            ->withPivot('id', 'role', 'status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get the moderators of this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile>
     */
    public function moderators(): BelongsToMany
    {
        return $this->members()->wherePivotIn('role', ['moderator', 'admin']);
    }

    /**
     * Get the media attached to this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Media>
     */
    public function pins(): MorphMany
    {
        return $this->morphMany(Pin::class, 'pinner')->orderBy('position');
    }

    /**
     * Get the pins of a specific kind attached to this space.
     * 
     * @param string $kind
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Pin>
     */
    public function pinsOf(string $kind): MorphMany
    {
        return $this->pins()->where('kind', $kind);
    }

    /**
     * Get the media attached to this space.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Media>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('position');
    }

    /**
     * Check if the space is a wall.
     *
     * @return bool
     */
    public function isWall(): bool
    {
        return $this->kind === 'wall';
    }
}
