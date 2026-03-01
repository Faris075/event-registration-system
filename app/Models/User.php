<?php
// ============================================================
// Model: User
// ------------------------------------------------------------
// Authentication identity model (extends Laravel Breeze scaffold).
// Separate from Attendee: a User has login credentials; an Attendee
// is a contact record linked by matching email address.
//
// Best practices applied:
//  ✔ $fillable whitelist prevents mass-assignment vulnerabilities
//  ✔ $hidden hides password + remember_token from JSON/array output
//  ✔ 'hashed' cast in casts() auto-bcrypts password on assignment
//  ✔ CURRENCIES constant keeps exchange-rate data centralised
//  ✔ convertPrice() handles null gracefully (returns 0.0)
//  ✔ getCurrencySymbolAttribute() provides a safe fallback ('$')
// ============================================================

namespace App\Models;

// Uncomment the line below to require email verification before login:
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Enables User::factory() for tests/seeders
use Illuminate\Foundation\Auth\User as Authenticatable; // Base class: wraps auth contracts & guards
use Illuminate\Notifications\Notifiable;               // Adds ->notify(), mail/database channels

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
    use HasFactory,  // Exposes User::factory() for database seeding and automated tests
        Notifiable;  // Enables $user->notify(new SomeNotification) pattern

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
        // Guard: null or 0.0 USD price means the event is free in every currency.
        if (! $usdPrice) {
            return 0.0; // Return typed float, not int 0, to match return-type declaration
        }

        // Null-coalesce to 1.0 so an unknown currency code simply leaves the USD price unchanged.
        $rate = self::CURRENCIES[$currency]['rate'] ?? 1.0;

        // round(..., 2) avoids floating-point artifacts like 0.9199999… → 0.92
        return round($usdPrice * $rate, 2);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',              // Display name; shown in UI and confirmation emails
        'email',             // Unique login identifier; must match Attendee.email to link history
        'password',          // Auto-hashed by the 'hashed' cast below — never store plain text
        'is_admin',          // Boolean flag; when true, unlocks admin routes via IsAdmin middleware
        'currency',          // ISO 4217 code (USD|EUR|GBP…); controls display currency on all prices
        'security_question', // Numeric ID referencing SecurityQuestionController::$questions map
        'security_answer',   // bcrypt hash of the lowercase-trimmed answer string
    ];

    /**
     * Accessor: resolved currency symbol string (e.g. '$', '€', '£').
     * Accessed as $user->currency_symbol in Blade.
     *
     * Double null-coalesce pattern:
     *  1. `$this->currency ?? 'USD'`  — default to USD if the column is null (fresh account)
     *  2. `... ?? '$'`                — fallback symbol if the currency code is somehow unknown
     */
    public function getCurrencySymbolAttribute(): string
    {
        // First coalesce: currency column may be null for accounts created before the column existed.
        // Second coalesce: safety net in case the CURRENCIES constant is modified later.
        return self::CURRENCIES[$this->currency ?? 'USD']['symbol'] ?? '$';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        // SECURITY: these fields will NOT appear in $user->toArray() / JSON responses.
        // This prevents accidental exposure via API routes or logging.
        'password',       // Hashed; never expose even the hash outside the auth layer
        'remember_token', // Long-lived session token; exposure allows session hijacking
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast the nullable timestamp to a Carbon instance so ->diffForHumans() etc. work.
            'email_verified_at' => 'datetime',
            // 'hashed' cast: Laravel automatically bcrypt-hashes the plain-text value
            // when $user->password = 'plain'; no manual Hash::make() needed here.
            'password' => 'hashed',
        ];
    }
}
