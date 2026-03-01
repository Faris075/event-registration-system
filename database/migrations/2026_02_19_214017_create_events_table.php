<?php

// =============================================================================
// Migration: create_events_table
// Defines the events entity — the central aggregate of the system.
// Registrations, attendees, and all capacity logic reference this table.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the events table.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();                                      // BIGINT unsigned auto-increment PK
            $table->string('title');                           // Short display name shown in listings
            $table->text('description');                       // Unlimited-length rich description (TEXT column)
            $table->dateTime('date_time');                     // Event start time; used for "upcoming" filtering
            $table->string('location');                        // Venue/address (free-text; no geo normalisation)
            $table->integer('capacity');                       // Maximum confirmed registrations allowed
            $table->decimal('price', 10, 2)->nullable();       // NULL = free event; 10 digits total, 2 decimal places
            // Draft → published = visible to public; cancelled/completed = read-only.
            // Lifecycle transitions enforced in EventController; DB only stores the value.
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->timestamps();                              // created_at + updated_at
        });
        // NOTE: Performance indexes (status, date_time) are added in a later migration
        //       (add_performance_indexes) to keep schema changes auditable and reversible.
    }

    /**
     * Drop the events table.
     * ⚠️  Registrations are CASCADE-deleted via their foreign key, so this will
     *    also remove all registration and payment history for every event.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
