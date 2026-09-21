<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'activated_at',
        'activation_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'activation_token',
    ];

    protected function casts(): array
    {
        return [
            'activated_at' => 'datetime',
            'password' => 'hashed', // gebruikt Hash::make() automatisch (TE-02)
        ];
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isTraveler(): bool
    {
        return $this->role === 'reiziger';
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class);
    }

    public function activityChoices(): HasMany
    {
        return $this->hasMany(ActivityChoice::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }
}
