<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In behandeling',
            self::Approved => 'Goedgekeurd',
            self::Rejected => 'Afgewezen',
        };
    }
}
