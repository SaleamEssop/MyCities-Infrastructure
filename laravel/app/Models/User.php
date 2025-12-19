<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact_number',
        'password',
        'is_admin',
        'is_super_admin',
        'is_demo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_demo' => 'boolean',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            // Delete all sites for this user (which will cascade to accounts, meters, and readings)
            foreach ($user->sites as $site) {
                $site->delete();
            }
        });
    }
}
