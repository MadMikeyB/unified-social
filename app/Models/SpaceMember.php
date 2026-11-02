<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $id
 * @property string $space_id
 * @property string $profile_id
 * @property string $role
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Space|null $space
 * @property-read \App\Models\Profile|null $profile
 */
#[Table('space_members')]
#[Fillable(['space_id', 'profile_id', 'role', 'status'])]
class SpaceMember extends Pivot
{
    use HasFactory, HasUuids;

    /**
     * Get the space that this member belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * Get the profile that this member belongs to.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Check if the member is a moderator or admin.
     *
     * @return bool
     */
    public function isModerator(): bool
    {
        return in_array($this->role, ['moderator', 'admin'], true);
    }
}
