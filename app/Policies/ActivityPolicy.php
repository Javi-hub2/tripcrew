<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function manage(User $user, Activity $activity): bool
    {
        return $user->isCoordinator();
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->isCoordinator();
    }

    /** FE-04: een reiziger mag alleen kiezen als hij ingedeeld is bij de bijbehorende reis. */
    public function choose(User $user, Activity $activity): bool
    {
        if (! $user->isTraveler()) {
            return false;
        }

        return $user->trips()->whereKey($activity->tripDay->trip_id)->exists();
    }
}
