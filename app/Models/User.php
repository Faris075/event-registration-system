<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Authenticatable user model.
 *
 * Extends Laravel's base Authenticatable to represent an application user.
 * Additional fields beyond the Breeze defaults:
 *
 *  - `is_admin`          (bool)   — grants access to admin-only routes and features
 *  - `security_question` (string) — plaintext question used for password recovery
 *  - `security_answer`   (string) — bcrypt-hashed answer (normalised to lowercase)
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Supported currencies: code => [symbol, name].
     */
    public const CURRENCIES = [
        'USD' => ['symbol' => '$',    'name' => 'US Dollar'],
        'EUR' => ['symbol' => '€',    'name' => 'Euro'],
        'GBP' => ['symbol' => '£',    'name' => 'British Pound'],
        'SAR' => ['symbol' => 'SAR',  'name' => 'Saudi Riyal'],
        'AED' => ['symbol' => 'AED',  'name' => 'UAE Dirham'],
        'JPY' => ['symbol' => '¥',    'name' => 'Japanese Yen'],
        'CAD' => ['symbol' => 'CA$',  'name' => 'Canadian Dollar'],
        'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar'],
        'EGP' => ['symbol' => 'EGP',  'name' => 'Egyptian Pound'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'currency',
        'security_question',
        'security_answer',
    ];

    /**
     * Resolved currency symbol for the user's chosen currency.
     */
    public function getCurrencySymbolAttribute(): string
    {
        return self::CURRENCIES[$this->currency ?? 'USD']['symbol'] ?? '$';
    }

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
}
