<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function days(): HasMany
    {
        return $this->hasMany(TripDay::class)->orderBy('date');
    }

    public function travelers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /** FE-06 foutgeval: reis met gekoppelde deelnemers kan niet zomaar verwijderd worden. */
    public function hasParticipants(): bool
    {
        return $this->travelers()->exists();
    }
}
