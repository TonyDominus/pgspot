<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Notifications\ResetPasswordIt;
use App\Notifications\VerifyEmailIt;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name', 'email', 'password', 'role', 'is_trusted_contributor',
    'accepted_terms_at', 'accepted_privacy_at', 'notify_contributions', 'notify_poi_updates',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'accepted_terms_at' => 'datetime',
            'accepted_privacy_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_trusted_contributor' => 'boolean',
            'notify_contributions' => 'boolean',
            'notify_poi_updates' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailIt);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordIt($token));
    }

    public function wantsContributionNotifications(): bool
    {
        return $this->notify_contributions && $this->hasVerifiedEmail();
    }

    public function wantsPoiUpdateNotifications(): bool
    {
        return $this->notify_poi_updates && $this->hasVerifiedEmail();
    }

    public function isAdmin(): bool
    {
        return $this->role->isAtLeast(UserRole::Admin);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function createdPois(): HasMany
    {
        return $this->hasMany(Poi::class, 'created_by');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function favoritePois(): BelongsToMany
    {
        return $this->belongsToMany(Poi::class, 'favorites')->withTimestamps();
    }
}
