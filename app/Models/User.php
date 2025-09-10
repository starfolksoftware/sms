<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserStatus;
use App\Notifications\UserInvitationNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'invitation_token',
        'invitation_sent_at',
        'invitation_accepted_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * Check if user is pending invitation.
     */
    public function isPendingInvite(): bool
    {
        return $this->status === UserStatus::PendingInvite;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Check if user is deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->status === UserStatus::Deactivated;
    }

    /**
     * Mark user as pending invitation.
     */
    public function markAsPendingInvite(): void
    {
    $this->update([
            'status' => UserStatus::PendingInvite,
            'invitation_token' => \Illuminate\Support\Str::random(32),
            'invitation_sent_at' => now(),
        ]);

    // Send invitation email
    $this->notify(new UserInvitationNotification($this));
    }

    /**
     * Mark user as accepting invitation.
     */
    public function acceptInvitation(): void
    {
        $this->update([
            'status' => UserStatus::Active,
            'invitation_token' => null,
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Deals owned by this user.
     */
    public function ownedDeals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Deal::class, 'owner_id');
    }

    /**
     * Deals created by this user.
     */
    public function createdDeals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Deal::class, 'created_by');
    }
}
