<?php

namespace App\Policies;

use App\Models\ActivityChoice;
use App\Models\User;

// TE-03 voorbeeld uit de briefing: /mijn-keuzes/14 van iemand anders openen -> 403.
class ActivityChoicePolicy
{
    public function view(User $user, ActivityChoice $choice): bool
    {
        return $choice->user_id === $user->id;
    }

    public function delete(User $user, ActivityChoice $choice): bool
    {
        if ($choice->user_id !== $user->id) {
            return false;
        }

        // FE-05: annuleren kan niet meer na de deadline.
        return ! $choice->activity->deadlineHasPassed();
    }
}
