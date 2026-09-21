<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

// TE-03: alleen coördinatoren mogen reizen beheren.
class TripPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function view(User $user, Trip $trip): bool
    {
        if ($user->isCoordinator()) {
            return true;
        }

        // Reiziger mag alleen zijn eigen reis zien.
        return $user->trips()->whereKey($trip->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isCoordinator();
    }

    public function update(User $user, Trip $trip): bool
    {
        return $user->isCoordinator();
    }

    public function delete(User $user, Trip $trip): bool
    {
        return $user->isCoordinator();
    }
}
