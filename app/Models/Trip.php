<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function activities(): HasManyThrough
    {
        return $this->hasManyThrough(Activity::class, TripDay::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    /** Vaste checklistpunten die de coördinator voor deze reis heeft vastgelegd. */
    public function requiredChecklistItems(): HasMany
    {
        return $this->hasMany(TripChecklistItem::class)->orderBy('id');
    }

    /**
     * Checklistvoortgang per deelnemer (user-id => percentage). Heeft de reis vaste punten,
     * dan telt alleen die voltooiing; anders de eigen punten van de reiziger.
     *
     * @return array<int, int>
     */
    public function checklistPercentages(): array
    {
        $requiredCount = $this->requiredChecklistItems()->count();

        return $this->travelers()
            ->with([
                'checklistItems' => fn ($q) => $q->where('trip_id', $this->id),
                'completedTripChecklistItems' => fn ($q) => $q->where('trip_id', $this->id),
            ])
            ->get()
            ->mapWithKeys(function (User $traveler) use ($requiredCount) {
                if ($requiredCount) {
                    $done = $traveler->completedTripChecklistItems->count();
                    $total = $requiredCount;
                } else {
                    $done = $traveler->checklistItems->where('checked', true)->count();
                    $total = $traveler->checklistItems->count();
                }

                return [$traveler->id => $total ? (int) round($done / $total * 100) : 0];
            })
            ->all();
    }

    /** FE-06 foutgeval: reis met gekoppelde deelnemers kan niet zomaar verwijderd worden. */
    public function hasParticipants(): bool
    {
        return $this->travelers()->exists();
    }
}
