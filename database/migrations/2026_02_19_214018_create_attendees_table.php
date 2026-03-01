<?php

// =============================================================================
// Migration: create_attendees_table
// An Attendee is a contact record (name + email) that can be re-used across
// multiple event registrations.  It is intentionally separate from the User
// table so that:
//   1. Guests (non-login users) can be registered by an admin.
//   2. A single person is deduped by email via updateOrCreate in the
//      EventRegistrationController, avoiding phantom duplicate rows.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the attendees table.
     */
    public function up(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();                          // Auto-increment BIGINT PK
            $table->string('name');                // Full name provided at registration
            $table->string('email')->unique();     // Deduplication key — one Attendee row per email
            $table->string('phone')->nullable();   // Optional contact phone number
            $table->string('company')->nullable(); // Optional organisation name
            $table->timestamps();                  // created_at + updated_at
        });
    }

    /**
     * Drop the attendees table.
     * ⚠️  Will cascade-delete all registrations linked to these attendees.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
