<?php

// =============================================================================
// Migration: add_performance_indexes
// Adds targeted indexes to prevent full-table scans on hot query paths.
// Kept as a separate migration (rather than inline in create_* migrations) so
// the performance additions are a discrete, auditable, reversible change.
//
// Index rationale:
//
//   events.status
//     → EventController::index() filters WHERE status = 'published' for non-admins.
//
//   events.date_time
//     → ORDER BY date_time and WHERE date_time > NOW() in listing queries.
//
//   registrations(event_id, status)  — composite
//     → Every capacity check queries WHERE event_id = ? AND status = 'confirmed'.
//       A composite index satisfies both predicates in one B-tree scan.
//
//   registrations.waitlist_position
//     → cancelAndPromote() orders by waitlist_position ASC to pick next in queue.
//
//   registrations(event_id, attendee_id) — UNIQUE
//     → Database-level duplicate-registration guard (defence-in-depth; app layer
//       also checks, but a race condition could bypass the app-layer check).
//       The unique constraint causes the INSERT to fail hard rather than silently
//       storing a duplicate row.
//
// NOTE: attendees.email is already UNIQUE from create_attendees_table.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add all performance indexes.
     */
    public function up(): void
    {
        // ── events indexes ────────────────────────────────────────────────────
        Schema::table('events', function (Blueprint $table) {
            $table->index('status',    'events_status_idx');     // Speeds up status = 'published' filter
            $table->index('date_time', 'events_date_time_idx');  // Speeds up chronological ordering + future-event filter
        });

        // ── registrations indexes ─────────────────────────────────────────────
        Schema::table('registrations', function (Blueprint $table) {
            // Composite index: covers WHERE event_id = X AND status = 'confirmed' in one scan.
            $table->index(['event_id', 'status'], 'registrations_event_status_idx');

            // Single-column index for ORDER BY waitlist_position ASC.
            $table->index('waitlist_position', 'registrations_waitlist_pos_idx');

            // Unique constraint prevents a second confirmed/waitlisted registration
            // for the same attendee+event pair (also enforces data integrity).
            $table->unique(['event_id', 'attendee_id'], 'registrations_event_attendee_unique');
        });
    }

    /**
     * Drop all indexes added in up().
     * Order mirrors up() for clarity; MySQL drops indexes independently of each other.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_idx');
            $table->dropIndex('events_date_time_idx');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropIndex('registrations_event_status_idx');
            $table->dropIndex('registrations_waitlist_pos_idx');
            $table->dropUnique('registrations_event_attendee_unique'); // dropUnique for UNIQUE indexes
        });
    }
};

