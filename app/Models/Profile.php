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
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $user_id
 * @property string $handle
 * @property string|null $display_name
 * @property string|null $bio
 * @property bool $is_private
 * @property array<string, mixed>|null $settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Space|null $wall
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reaction> $reactions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $media
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $following
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $followers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $pendingFollowers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profile> $friends
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Space> $spaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Pin> $pins
 */
#[Table('profiles')]
#[Fillable(['user_id', 'handle', 'display_name', 'bio', 'is_private', 'settings'])]
class Profile extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * The "booted" method of the model.
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wall space for this profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne<\App\Models\Space, $this>
     */
    public function wall(): MorphOne
    {
        return $this->morphOne(Space::class, 'owner')->where('kind', 'wall');
    }

    /**
     * Get the posts authored by this profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Get the reactions made by this profile.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Reaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get the media attached to this profile, such as its avatar and banner.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('position');
    }

    /**
     * Get the profiles this profile follows (accepted only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile, $this, \App\Models\Follow>
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
     * Get the profiles that follow this profile (accepted only).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile, $this, \App\Models\Follow>
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
     * Get the profiles waiting for this profile to accept their follow request.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile, $this, \App\Models\Follow>
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
     * Get the profiles this profile has a mutual accepted follow with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Profile, $this, \App\Models\Follow>
     */
    public function friends(): BelongsToMany
    {
        return $this->following()
            ->whereIn('profiles.id', $this->followers()->select('profiles.id'));
    }

    /**
     * Follow another profile: accepted straight away unless it's private.
     * An existing follow or request is left as it is.
     */
    public function follow(Profile $profile): Follow
    {
        return $this->followRow($profile, $profile->is_private ? 'pending' : 'accepted');
    }

    /**
     * Ask to follow another profile, whatever its privacy.
     * An existing follow is never downgraded back to a request.
     */
    public function requestFollow(Profile $profile): Follow
    {
        return $this->followRow($profile, 'pending');
    }

    /**
     * Accept a pending request from another profile, and optionally follow them back.
     */
    public function acceptFollower(Profile $profile, bool $reciprocate = false): void
    {
        Follow::where('follower_id', $profile->id)
            ->where('followee_id', $this->id)
            ->where('status', 'pending')
            ->update(['status' => 'accepted']);

        if ($reciprocate) {
            // accepting someone you'd asked to follow settles that request too
            Follow::updateOrCreate(
                ['follower_id' => $this->id, 'followee_id' => $profile->id],
                ['status' => 'accepted'],
            );
        }
    }

    /**
     * Stop following another profile, or withdraw a pending request.
     */
    public function unfollow(Profile $profile): void
    {
        Follow::where('follower_id', $this->id)->where('followee_id', $profile->id)->delete();
    }

    /**
     * Get the spaces this profile is an accepted member of.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Space, $this, \App\Models\SpaceMember>
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
     * Get everything this profile has pinned, in order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Pin, $this>
     */
    public function pins(): MorphMany
    {
        return $this->morphMany(Pin::class, 'pinner')->orderBy('position');
    }

    /**
     * Get this profile's pins of one kind, in order.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany<\App\Models\Pin, $this>
     */
    public function pinsOf(string $kind): MorphMany
    {
        return $this->pins()->where('kind', $kind);
    }

    /**
     * Insert the follow row, or return the one that's already there untouched.
     * Relies on the unique index on follower_id and followee_id.
     */
    protected function followRow(Profile $profile, string $status): Follow
    {
        return Follow::createOrFirst(
            ['follower_id' => $this->id, 'followee_id' => $profile->id],
            ['status' => $status],
        );
    }
}