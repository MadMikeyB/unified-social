<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $post_id
 * @property string $kind
 * @property string $type
 * @property int $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Profile|null $profile
 * @property-read \App\Models\Post|null $post
 */
#[Table('reactions')]
#[Fillable(['profile_id', 'post_id', 'kind', 'type', 'value'])]
class Reaction extends Model
{
    use HasFactory, HasUuids;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saved(fn (Reaction $reaction) => $reaction->syncScore());
        static::deleted(fn (Reaction $reaction) => $reaction->syncScore());
    }

    /**
     * Get the profile that made this reaction.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class)->withTrashed();
    }

    /**
     * Get the post that this reaction is for.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withTrashed();
    }

    /**
     * Synchronize the score of the associated post.
     *
     * @return void
     */
    protected function syncScore(): void
    {
        if ($this->kind !== 'vote') {
            return;
        }

        Post::withTrashed()->whereKey($this->post_id)->update([
            'score' => Reaction::where('post_id', $this->post_id)->where('kind', 'vote')->sum('value'),
        ]);
    }
}
