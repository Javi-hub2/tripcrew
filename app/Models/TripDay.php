<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripDay extends Model
{
    use HasFactory;

    protected $fillable = ['trip_id', 'date'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function programItems(): HasMany
    {
        return $this->hasMany(ProgramItem::class)->orderBy('time');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
