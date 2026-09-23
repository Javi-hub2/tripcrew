<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = ['trip_day_id', 'name', 'capacity', 'deadline'];

    protected function casts(): array
    {
        return ['deadline' => 'datetime'];
    }

    public function tripDay(): BelongsTo
    {
        return $this->belongsTo(TripDay::class);
    }

    public function choices(): HasMany
    {
        return $this->hasMany(ActivityChoice::class);
    }

    /** FE-04: aantal bezette plekken. */
    public function takenSeats(): int
    {
        return $this->choices()->count();
    }

    public function seatsLeft(): int
    {
        return max(0, $this->capacity - $this->takenSeats());
    }

    public function deadlineHasPassed(): bool
    {
        return now()->greaterThan($this->deadline);
    }
}
