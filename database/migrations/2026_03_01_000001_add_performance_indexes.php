<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes to prevent full-table scans on commonly filtered columns.
 *
 * events
 *   - status:    filtered in EventController (published / non-admin queries)
 *   - date_time: ORDER BY and WHERE date_time > now() filters
 *
 * registrations
 *   - (event_id, status)        composite: every capacity/count query uses both
 *   - waitlist_position         ORDER BY when promoting waitlisted attendees
 *   - (event_id, attendee_id)   UNIQUE: database-level duplicate-registration guard
 *                                (app-layer check already exists; this is defence-in-depth)
 *
 * attendees.email is already UNIQUE from the original create_attendees_table migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('status',    'events_status_idx');
            $table->index('date_time', 'events_date_time_idx');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->index(['event_id', 'status'], 'registrations_event_status_idx');
            $table->index('waitlist_position',     'registrations_waitlist_pos_idx');
            // Unique constraint prevents two rows with the same event+attendee pair.
            $table->unique(['event_id', 'attendee_id'], 'registrations_event_attendee_unique');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_idx');
            $table->dropIndex('events_date_time_idx');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('registrations_event_status_idx');
            $table->dropIndex('registrations_waitlist_pos_idx');
            $table->dropUnique('registrations_event_attendee_unique');
        });
    }
};

