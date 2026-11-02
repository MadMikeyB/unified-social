<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $space_id
 * @property string|null $parent_id
 * @property string|null $root_id
 * @property string|null $quoted_post_id
 * @property string|null $reposted_post_id
 * @property string|null $title
 * @property string|null $slug
 * @property string|null $body
 * @property string $visibility
 * @property string|null $client
 * @property bool $is_locked
 * @property int $reply_count
 * @property int $repost_count
 * @property \Illuminate\Support\Carbon|null $last_activity_at
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Profile|null $author
 * @property-read \App\Models\Space|null $space
 * @property-read \App\Models\Post|null $parent
 * @property-read \App\Models\Post|null $root
 * @property-read \App\Models\Post|null $quoted
 * @property-read \App\Models\Post|null $reposted
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $conversation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reaction> $re
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Media> $media
 */
#[Table('posts')]
#[Fillable(['profile_id', 'space_id', 'parent_id', 'root_id', 'quoted_post_id', 'reposted_post_id', 'title', 'slug', 'body', 'visibility', 'client', 'is_locked', 'last_activity_at', 'edited_at'])]
class Post extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'last_activity_at' => 'datetime',
            'edited_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     * 
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (Post $post) {
            if ($post->parent_id && ! $post->root_id) {
                $parent = Post::withTrashed()->findOrFail($post->parent_id);
                $post->root_id = $parent->root_id ?? $parent->id;
                $post->space_id ??= $parent->space_id;
            }

            $post->last_activity_at ??= now();
        });

        static::created(function (Post $post) {
            if ($post->root_id) {
                Post::withTrashed()->whereKey($post->root_id)
                    ->increment('reply_count', 1, ['last_activity_at' => now()]);
            }

            if ($post->reposted_post_id) {
                Post::withTrashed()->whereKey($post->reposted_post_id)->increment('repost_count');
            }
        });

        // replies are kept so threads still read; front ends show the gap as "[deleted]"
        static::deleted(function (Post $post) {
            if ($post->isForceDeleting()) {
                return;
            }

            $post->adjustCounters(-1);
            $post->media()->lazyById()->each->delete();
        });

        static::restored(function (Post $post) {
            $post->adjustCounters(1);
        });
    }

    /**
     * Get the author of the post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id')->withTrashed();
    }

    /**
     * Get the space the post belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class)->withTrashed();
    }

    /**
     * Get the parent post if this is a reply.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_id')->withTrashed();
    }

    /**
     * Get the root post of the thread if this is a reply.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function root(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'root_id')->withTrashed();
    }

    /**
     * Get the quoted post if this is a quote.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function quoted(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'quoted_post_id')->withTrashed();
    }

    /**
     * Get the reposted post if this is a repost.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reposted(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'reposted_post_id')->withTrashed();
    }

    /**
     * Get the child posts that are replies to this post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id');
    }

    /**
     * Get the posts that are part of the same conversation thread.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversation(): HasMany
    {
        return $this->hasMany(Post::class, 'root_id');
    }

    /**
     * Get the reactions to this post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    /**
     * Get the media attached to this post.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('position');
    }

    /**
     * Check if the post is a root post (not a reply).
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        return $this->root_id === null;
    }

    /**
     * Check if the post is a reply to another post.
     *
     * @return bool
     */
    public function isRepost(): bool
    {
        return $this->reposted_post_id !== null && blank($this->body);
    }

    /**
     * Check if the post is a quote of another post.
     *
     * @return bool
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('root_id');
    }

    /**
     * Scope a query to only include replies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('root_id');
    }

    /**
     * Scope a query to only include posts in the given spaces.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param iterable|Builder $spaceIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInSpaces(Builder $query, iterable|Builder $spaceIds): Builder
    {
        return $query->whereIn('space_id', $spaceIds);
    }

    /**
     * Scope a query to only include posts by the given authors.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param iterable|Builder $profileIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAuthors(Builder $query, iterable|Builder $profileIds): Builder
    {
        return $query->whereIn('profile_id', $profileIds);
    }

    /**
     * Scope a query to only include posts visible to the given viewer.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\Profile|null $viewer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleTo(Builder $query, ?Profile $viewer): Builder
    {
        return $query->where(function (Builder $q) use ($viewer) {
            $q->where('visibility', 'public');

            if (! $viewer) {
                return;
            }

            $q->orWhere('profile_id', $viewer->id)
                ->orWhere(fn (Builder $q) => $q->where('visibility', 'followers')
                    ->whereIn('profile_id', $viewer->following()->select('profiles.id')))
                ->orWhere(fn (Builder $q) => $q->where('visibility', 'friends')
                    ->whereIn('profile_id', $viewer->friends()->select('profiles.id')))
                ->orWhere(fn (Builder $q) => $q->where('visibility', 'space')
                    ->whereIn('space_id', $viewer->spaces()->select('spaces.id')));
        });
    }

    /**
     * Adjust the reply and repost counters for the root and reposted posts.
     *
     * @param int $by
     * @return void
     */
    protected function adjustCounters(int $by): void
    {
        $targets = array_filter([
            'reply_count' => $this->root_id,
            'repost_count' => $this->reposted_post_id,
        ]);

        foreach ($targets as $column => $id) {
            $query = Post::withTrashed()->whereKey($id);

            // the counters are unsigned, so never take one below zero
            $by > 0
                ? $query->increment($column, $by)
                : $query->where($column, '>=', abs($by))->decrement($column, abs($by));
        }
    }
}
