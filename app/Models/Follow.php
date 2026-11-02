<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $follower_id
 * @property string $followee_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Profile|null $follower
 * @property-read \App\Models\Profile|null $followee
 */
#[Table('follows')]
#[Fillable(['follower_id', 'followee_id', 'status'])]
class Follow extends Pivot
{
    use HasFactory, HasUuids;

    /**
     * Get the follower profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'follower_id');
    }

    /**
     * Get the followee profile.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function followee(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'followee_id');
    }

    /**
     * Check if the follow request is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
