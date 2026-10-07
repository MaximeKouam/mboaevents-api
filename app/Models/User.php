<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\OrganizerPage;
use App\Models\Follow;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Wallet;


class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
            'password' => 'hashed',
        ];
    }


    public function wallet(): HasOne
{
    return $this->hasOne(Wallet::class);
}

public function organizerPages(): HasMany
{
    return $this->hasMany(OrganizerPage::class);
}

public function follows(): HasMany
{
    return $this->hasMany(Follow::class);
}

public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}

public function tickets(): HasMany
{
    return $this->hasMany(Ticket::class);
}

}
