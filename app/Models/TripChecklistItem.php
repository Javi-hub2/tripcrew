<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Vast checklistpunt dat de coördinator per reis vastlegt; elke reiziger vinkt het zelf af. */
class TripChecklistItem extends Model
{
    protected $fillable = ['trip_id', 'label'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** Reizigers die dit punt hebben afgevinkt. */
    public function completedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function isDoneBy(User $user): bool
    {
        return $this->completedBy()->whereKey($user->id)->exists();
    }
}
