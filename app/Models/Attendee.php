<?php
// ============================================================
// Model: Attendee
// ------------------------------------------------------------
// Represents a person who registers for events.
// Intentionally SEPARATE from the User model:
//   - A User is an authentication identity (login/password).
//   - An Attendee is a contact record attached to a Registration.
//   - One User can be matched to one Attendee via shared email.
//
// Best practices applied:
//  ✔ $fillable whitelist limits mass-assignable fields
//  ✔ email is unique at DB level (see migration) — enforced per-record
//  ✔ Relationship method typed with HasMany return hint
//  ✔ phone / company are nullable (not every registration needs them)
// ============================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Allows Attendee::factory() in tests
use Illuminate\Database\Eloquent\Model;                // Base Eloquent model
use Illuminate\Database\Eloquent\Relations\HasMany;    // Return-type hint

/**
 * @property int         $id
 * @property string      $name
 * @property string      $email     Unique; used to link an attendee to a User account
 * @property string|null $phone
 * @property string|null $company
 */
class Attendee extends Model
{
    use HasFactory; // Required for database seeding and feature tests

    /**
     * Mass-assignable fields.
     * Using updateOrCreate(['email'=>...], [...]) in the registration flow
     * requires all updated fields to be listed here.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',    // Full name as provided on the registration form
        'email',   // Contact email; unique in DB — used as the attendee identifier
        'phone',   // Optional contact phone; validated with regex in the controller
        'company', // Optional employer / organisation name
    ];

    // ──────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────

    /**
     * All event registrations for this attendee across all events.
     * One attendee can register for many separate events.
     * Foreign key: registrations.attendee_id
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class); // registrations.attendee_id = this->id
    }
}
