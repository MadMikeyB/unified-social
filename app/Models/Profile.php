<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $handle
 * @property string|null $display_name
 * @property string|null $bio
 * @property bool $is_private
 * @property array|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\Space|null $wall
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reaction> $re
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $followers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $pending
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $friends
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Space> $spaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pins
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pins
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pinsOf
 */
#[Table('profiles')]
#[Fillable(['user_id', 'handle', 'display_name', 'bio', 'is_private', 'settings'])]
class Profile extends Model
{
    use HasUuids, SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
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
        static::created(function (Profile $profile) {
            $profile->wall()->create([
                'kind' => 'wall',
                'name' => $profile->display_name ?? $profile->handle,
                'slug' => 'u-'.$profile->handle,
            ]);
        });

        // soft deletes are an UPDATE, so the database cascades never fire: do it here
        static::deleted(function (Profile $profile) {
            if ($profile->isForceDeleting()) {
                return;
            }

            $profile->forceFill(['handle' => $profile->handle.'#deleted-'.$profile->id])->saveQuietly();

            $profile->posts()->lazyById()->each->delete();
            $profile->media()->lazyById()->each->delete();
            $profile->wall?->delete();
        });
    }

    /**
     * Get the user that owns this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wall space for this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function wall(): MorphOne
    {
        return $this->morphOne(Space::class, 'owner')->where('kind', 'wall');
    }

    /**
     * Get the posts authored by this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the reactions made by this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get the media attached to this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('position');
    }

    /**
     * Get the profiles that this profile is following.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'follows', 'follower_id', 'followee_id')
            ->using(Follow::class)
            ->withPivot('id', 'status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get the profiles that are following this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'follows', 'followee_id', 'follower_id')
            ->using(Follow::class)
            ->withPivot('id', 'status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get the profiles that have requested to follow this profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function pendingFollowers(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class, 'follows', 'followee_id', 'follower_id')
            ->using(Follow::class)
            ->withPivot('id', 'status')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }

    /**
     * Get the profiles that are friends with this profile (mutual accepted follows).
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friends(): BelongsToMany
    {
        return $this->following()
            ->whereIn('profiles.id', $this->followers()->select('profiles.id'));
    }

    /**
     * Follow another profile. (accepted straight away unless the profile is private)
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function follow(Profile $profile): void
    {
        $this->following()->syncWithoutDetaching([
            $profile->id => ['status' => $profile->is_private ? 'pending' : 'accepted'],
        ]);
    }

    /**
     * Request to follow another profile. (always pending, regardless of privacy)
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function requestFollow(Profile $profile): void
    {
        $this->following()->syncWithoutDetaching([$profile->id => ['status' => 'pending']]);
    }

    /**
     * Accept a follow request from another profile. Optionally reciprocate the follow.
     *
     * @param \App\Models\Profile $profile
     * @param bool $reciprocate
     * @return void
     */
    public function acceptFollower(Profile $profile, bool $reciprocate = false): void
    {
        $this->pendingFollowers()->updateExistingPivot($profile->id, ['status' => 'accepted']);

        if ($reciprocate) {
            $this->following()->syncWithoutDetaching([$profile->id => ['status' => 'accepted']]);
        }
    }

    /**
     * Unfollow another profile or remove a follow request. (removes the row whatever its status, so a pending request can be withdrawn too)
     *
     * @param \App\Models\Profile $profile
     * @return void
     */
    public function unfollow(Profile $profile): void
    {
        Follow::where('follower_id', $this->id)->where('followee_id', $profile->id)->delete();
    }

    /**
     * Get the spaces that this profile is a member of.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function spaces(): BelongsToMany
    {
        return $this->belongsToMany(Space::class, 'space_members')
            ->using(SpaceMember::class)
            ->withPivot('id', 'role', 'status')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * Get the pins for this profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pins(): MorphMany
    {
        return $this->morphMany(Pin::class, 'pinner')->orderBy('position');
    }

    /**
     * Get the pins of a specific kind for this profile.
     *
     * @param string $kind
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function pinsOf(string $kind): MorphMany
    {
        return $this->pins()->where('kind', $kind);
    }
}
