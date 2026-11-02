<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $profile_id
 * @property string $mediable_type
 * @property string $mediable_id
 * @property string $role
 * @property string $disk
 * @property string $path
 * @property string $mime
 * @property string|null $alt
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Profile $uploader
 * @property-read \App\Models\Post|\App\Models\Profile|\App\Models\Space|null $mediable
 */
#[Table('media')]
#[Fillable(['profile_id', 'mediable_type', 'mediable_id', 'role', 'disk', 'path', 'mime', 'alt', 'position'])]
class Media extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // the file only goes when the row is gone for good
        static::forceDeleted(fn (Media $media) => Storage::disk($media->disk)->delete($media->path));
    }

    /**
     * Get the profile that uploaded this media.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Profile, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'profile_id')->withTrashed();
    }

    /**
     * Get the post, profile or space this media is attached to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\App\Models\Post|\App\Models\Profile|\App\Models\Space, $this>
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the public URL for the media file.
     * 
     * @return string
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}