<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * @property string $id
 * @property string $pinner_type
 * @property string $pinner_id
 * @property string $pinnable_type
 * @property string $pinnable_id
 * @property string $kind
 * @property int $position
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Profile|\App\Models\Space $pinner
 * @property-read \App\Models\Post|\App\Models\Profile|null $pinnable
 */
#[Table('pins')]
#[Fillable(['pinner_type', 'pinner_id', 'pinnable_type', 'pinnable_id', 'kind', 'position'])]
class Pin extends Model
{
    use HasUuids;

    /**
     * Get the profile or space that made this pin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\App\Models\Profile|\App\Models\Space, $this>
     */
    public function pinner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the post or profile that was pinned.
     * Soft-deleted targets resolve to null, so they drop out of the list rather than showing as "[deleted]".
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo<\App\Models\Post|\App\Models\Profile, $this>
     */
    public function pinnable(): MorphTo
    {
        return $this->morphTo();
    }
}