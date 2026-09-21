<?php

namespace App\Policies;

use App\Models\ChecklistItem;
use App\Models\User;

class ChecklistItemPolicy
{
    public function update(User $user, ChecklistItem $item): bool
    {
        return $item->user_id === $user->id;
    }
}
