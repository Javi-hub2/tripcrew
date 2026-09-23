<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'start_date', 'end_date', 'practical_info'];

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

    /** Alle inschrijvingen, ongeacht status. Hierop wordt attach() gedaan. */
    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['status', 'requested_at', 'decided_at', 'decided_by'])
            ->withTimestamps();
    }

    /** Alleen goedgekeurde deelnemers (FE-08). */
    public function travelers(): BelongsToMany
    {
        return $this->registrations()->wherePivot('status', RegistrationStatus::Approved->value);
    }

    public function pendingRegistrations(): BelongsToMany
    {
        return $this->registrations()->wherePivot('status', RegistrationStatus::Pending->value);
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
