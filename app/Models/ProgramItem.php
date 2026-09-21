<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramItem extends Model
{
    use HasFactory;

    protected $fillable = ['trip_day_id', 'time', 'title', 'location'];

    public function tripDay(): BelongsTo
    {
        return $this->belongsTo(TripDay::class);
    }
}
