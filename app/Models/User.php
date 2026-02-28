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
     * Supported currencies: code => [symbol, name, rate].
     *
     * `rate` is the exchange rate FROM USD (1 USD = X <currency>).
     * All event prices are stored in USD; multiply by rate to display in another currency.
     * Rates are indicative and fixed for demo purposes.
     */
    public const CURRENCIES = [
        'USD' => ['symbol' => '$',    'name' => 'US Dollar',         'rate' => 1.00],
        'EUR' => ['symbol' => '€',    'name' => 'Euro',              'rate' => 0.92],
        'GBP' => ['symbol' => '£',    'name' => 'British Pound',     'rate' => 0.79],
        'SAR' => ['symbol' => 'SAR ', 'name' => 'Saudi Riyal',       'rate' => 3.75],
        'AED' => ['symbol' => 'AED ', 'name' => 'UAE Dirham',        'rate' => 3.67],
        'JPY' => ['symbol' => '¥',    'name' => 'Japanese Yen',      'rate' => 149.50],
        'CAD' => ['symbol' => 'CA$',  'name' => 'Canadian Dollar',   'rate' => 1.36],
        'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar', 'rate' => 1.53],
        'EGP' => ['symbol' => 'EGP ', 'name' => 'Egyptian Pound',    'rate' => 48.50],
    ];

    /**
     * Convert a USD price to the given currency using the fixed indicative rates.
     *
     * @param  float|null  $usdPrice  The price stored in the database (USD).
     * @param  string      $currency  Target currency code (must exist in CURRENCIES).
     * @return float
     */
    public static function convertPrice(?float $usdPrice, string $currency): float
    {
        if (! $usdPrice) {
            return 0.0;
        }

        $rate = self::CURRENCIES[$currency]['rate'] ?? 1.0;

        return round($usdPrice * $rate, 2);
    }

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
