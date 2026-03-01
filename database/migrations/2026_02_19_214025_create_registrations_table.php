<?php

// =============================================================================
// Migration: create_registrations_table
// The join table that links an Attendee to an Event and tracks the lifecycle
// of that booking (status, payment, position in the waitlist).
//
// Additional columns added by later migrations:
//   - waitlist_position (add_waitlist_position_to_registrations_table)
//   - is_admin_override (add_is_admin_override_to_registrations_table)
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the registrations table.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id(); // BIGINT auto-increment PK

            // Foreign key to events.id — cascade delete removes registrations when an event is deleted.
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');

            // Foreign key to attendees.id — cascade delete removes registrations when an attendee is deleted.
            $table->foreignId('attendee_id')->constrained('attendees')->onDelete('cascade');

            // Recorded at insertion time via useCurrent() → TIMESTAMP DEFAULT CURRENT_TIMESTAMP.
            // Separate from created_at so it semantically represents "when the booking was made".
            $table->timestamp('registration_date')->useCurrent();

            // Booking lifecycle:
            //   confirmed  — seat reserved, payment may still be pending.
            //   waitlisted — capacity full; attendee is queued for promotion.
            //   cancelled  — attendee withdrew or event was cancelled.
            $table->enum('status', ['confirmed', 'waitlisted', 'cancelled'])->default('confirmed');

            // Payment lifecycle:
            //   pending  — payment not yet received (default for free events too).
            //   paid     — payment processed successfully.
            //   refunded — money returned (on cancellation of a paid event).
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending');

            $table->timestamps(); // created_at + updated_at (Eloquent auto-manages)
        });
        // NOTE: A unique composite index (event_id, attendee_id) is added later in
        //       add_performance_indexes to prevent duplicate registrations at DB level.
    }

    /**
     * Drop the registrations table.
     */
    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
