<?php
// ============================================================
// Model: Event
// ------------------------------------------------------------
// Represents a single schedulable event in the system.
// Stores metadata (title, location, capacity, price, status)
// and exposes helpers for capacity / waitlist calculations.
//
// Best practices applied:
//  ✔ $fillable whitelist prevents mass-assignment vulnerabilities
//  ✔ $casts casts date_time to Carbon so comparison helpers work
//  ✔ Accessors reuse eager-loaded withCount values (N+1 prevention)
//  ✔ Explicit return types on every method
//  ✔ max(0, …) guards against negative capacity results
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Enables factory()-based seeding & testing
use Illuminate\Database\Eloquent\Model;                // Base Eloquent ORM model
use Illuminate\Database\Eloquent\Relations\HasMany;    // Return-type hint for relationship methods

/**
 * @property int         $id
 * @property string      $title
 * @property string      $description
 * @property \Carbon\Carbon $date_time   Auto-cast to Carbon via $casts
 * @property string      $location
 * @property int         $capacity      Maximum number of confirmed attendees
 * @property float|null  $price         Event price in USD; null means free
 * @property string      $status        One of: draft | published | cancelled | completed
 * @property int|null    $confirmed_count  Injected by withCount(); falls back to live query
 */
class Event extends Model
{
    use HasFactory; // Allows Event::factory()->create() in tests and seeders

    /**
     * Mass-assignable columns.
     * Only these fields can be set via Event::create([...]) or $event->fill([...]).
     * Omitting a column here blocks mass-assignment even if it exists in the DB.
     *
     * @var list<string>
     */
    protected $fillable = ['title', 'description', 'date_time', 'location', 'capacity', 'price', 'status'];

    /**
     * Automatic type casts.
     * 'datetime' converts the raw DB string into a Carbon instance so we can call
     * ->toDateString(), ->greaterThanOrEqualTo(), etc. without manual parsing.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_time' => 'datetime', // Cast to \Carbon\Carbon on read; stored as DATETIME in DB
    ];

    // ──────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────

    /**
     * All registrations linked to this event (confirmed + waitlisted + cancelled).
     *
     * Lazy-loaded: use ->with('registrations') on the query to eager-load.
     */
    public function registrations(): HasMany
    {
        // Foreign key is inferred as 'event_id' on the registrations table.
        return $this->hasMany(Registration::class);
    }

    /**
     * Only the confirmed registrations for this event.
     * Adds a WHERE status = 'confirmed' scope on top of the base relationship.
     * Useful for sub-queries and eager-load constraints.
     */
    public function confirmedRegistrations(): HasMany
    {
        return $this->registrations()->where('status', 'confirmed');
    }

    // ──────────────────────────────────────────────────────────
    // Accessors (snake_case magic properties)
    // ──────────────────────────────────────────────────────────

    /**
     * Get the number of confirmed attendees.
     *
     * N+1 prevention: controllers call
     *   ->withCount(['registrations as confirmed_count' => fn($q) => $q->where('status','confirmed')])
     * which pre-populates $this->attributes['confirmed_count'] in one SQL aggregate.
     * If that attribute exists, return it directly instead of issuing another COUNT query.
     * If it does NOT exist (e.g. a single-record show page), fall back to a live count.
     *
     * Accessed via $event->confirmed_count  (Laravel strips get…Attribute prefix)
     */
    public function getConfirmedCountAttribute(): int
    {
        // Check the raw attribute bag — withCount stores the value here.
        if (array_key_exists('confirmed_count', $this->attributes)) {
            return (int) $this->attributes['confirmed_count']; // Cast to int; DB may return string
        }

        // Fallback: live aggregation query (one extra DB round-trip).
        return $this->confirmedRegistrations()->count();
    }

    /**
     * Get the number of open seats left for confirmed bookings.
     * Delegates to confirmed_count so eager-loaded values are reused if available.
     *
     * Accessed via $event->remaining_spot
     */
    public function getRemainingSpotAttribute(): int
    {
        // max(0,…) prevents returning a negative value if admin overrides pushed
        // confirmed registrations above the stated capacity.
        return max(0, $this->capacity - $this->confirmed_count);
    }

    // ──────────────────────────────────────────────────────────
    // Business-logic helpers
    // ──────────────────────────────────────────────────────────

    /**
     * The number of waitlist slots available for this event.
     * Fixed at 25 % of capacity, with a hard minimum of 1
     * so even a capacity-1 event can have at least one waitlisted person.
     */
    public function waitlistCapacity(): int
    {
        // ceil ensures fractional results round up (e.g. 3 for capacity 10 → 3, not 2).
        return (int) max(1, ceil($this->capacity * 0.25));
    }

    /**
     * Live count of registrations currently in waitlisted status.
     * Issues a COUNT query every time it is called — consider caching or
     * using withCount if called inside a loop.
     */
    public function waitlistCount(): int
    {
        return $this->registrations()->where('status', 'waitlisted')->count();
    }

    /**
     * Count of registrations that were placed by an admin bypass
     * (is_admin_override = true) and are still confirmed.
     * Admins are limited to 5 such overrides per event (enforced in the controller).
     */
    public function adminOverrideCount(): int
    {
        return $this->registrations()
            ->where('is_admin_override', true)   // Flag set during force-add flow
            ->where('status', 'confirmed')        // Only active overrides count toward the limit
            ->count();
    }

    /**
     * Remaining confirmed capacity.
     * Identical result to getRemainingSpotAttribute but available as a named method
     * for use in non-view code paths (e.g. service classes, commands).
     */
    public function remainingCapacity(): int
    {
        // Delegates to the accessor so pre-loaded withCount values are respected.
        return (int) max(0, $this->capacity - $this->confirmed_count);
    }

    /**
     * Whether the event's scheduled date/time has already passed.
     * Used to block new registrations on past events (registration form guard)
     * and to auto-label events as completed in the UI.
     */
    public function isCompleted(): bool
    {
        // now() returns current Carbon timestamp in the app timezone (config/app.php).
        // greaterThanOrEqualTo handles the exact-match boundary correctly.
        return now()->greaterThanOrEqualTo($this->date_time);
    }
}
