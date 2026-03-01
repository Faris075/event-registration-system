<?php
// ============================================================
// Model: Registration
// ------------------------------------------------------------
// Pivot/join table between Event and Attendee with extra columns.
// Tracks the booking lifecycle:
//   status:         confirmed → waitlisted → cancelled
//   payment_status: pending  → paid       → refunded
//   waitlist_position: 1-based queue position; null when not waitlisted
//   is_admin_override: bypasses capacity cap (max 5 per event)
//
// Best practices applied:
//  ✔ Explicit $fillable prevents accidental mass-assignment
//  ✔ Typed relationship methods with BelongsTo hints
//  ✔ $casts converts is_admin_override boolean from tinyint correctly
//  ✔ Foreign keys enforced at DB level (onDelete cascade) in migration
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Factory support for tests
use Illuminate\Database\Eloquent\Model;                // Base Eloquent model
use Illuminate\Database\Eloquent\Relations\BelongsTo;  // Return-type hint for belongs-to

/**
 * @property int         $id
 * @property int         $event_id
 * @property int         $attendee_id
 * @property string      $registration_date  Defaults to NOW() via useCurrent() in migration
 * @property string      $status             confirmed | waitlisted | cancelled
 * @property int|null    $waitlist_position  Position in queue; null when not waitlisted
 * @property string      $payment_status     pending | paid | refunded
 * @property bool        $is_admin_override  True when placed by admin force-add
 */
class Registration extends Model
{
    use HasFactory; // Enables Registration::factory() in feature tests

    /**
     * Mass-assignable columns.
     * All columns that the controller sets via ::create([...]) or ->update([...])
     * must be listed here; unlisted columns are silently ignored on mass-assign.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',           // FK → events.id  (cascade delete)
        'attendee_id',        // FK → attendees.id (cascade delete)
        'registration_date',  // Timestamp; defaults to current time in DB
        'status',             // Business status: confirmed | waitlisted | cancelled
        'waitlist_position',  // Nullable int; 1 = first in queue, null = not queued
        'payment_status',     // Payment state: pending | paid | refunded
        'is_admin_override',  // Boolean flag; bypasses capacity enforcement
    ];

    /**
     * Attribute casts for correct PHP types.
     * Without this, `is_admin_override` arrives as '0'/'1' string from MySQL.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin_override' => 'boolean', // Tinyint(1) in DB → bool in PHP
    ];

    // ──────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────

    /**
     * The event this registration belongs to.
     * Eager-load with ->with('event') when listing registrations to avoid N+1.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class); // event_id → events.id
    }

    /**
     * The attendee who made this registration.
     * Eager-load with ->with('attendee') in admin registration lists.
     */
    public function attendee(): BelongsTo
    {
        return $this->belongsTo(Attendee::class); // attendee_id → attendees.id
    }
}
