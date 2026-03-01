<?php

// =============================================================================
// Migration: add_waitlist_position_to_registrations_table
// Adds an ordinal position column to track queue order within the waitlist.
//
// Why a separate migration?
//   The waitlist feature was added after the initial schema, demonstrating the
//   recommended practice of additive (non-destructive) schema evolution.
//
// Column semantics:
//   - NULL   → registration is NOT on the waitlist (confirmed or cancelled).
//   - 1      → first in queue to be promoted when a spot opens.
//   - n      → nth in queue; assigned sequentially via COALESCE(MAX+1, 1).
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add waitlist_position column to registrations.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // unsignedInteger: position is always >= 1; no negative values needed.
            // nullable: only waitlisted rows carry a position value.
            // after('status'): placed next to the status column for readability.
            $table->unsignedInteger('waitlist_position')->nullable()->after('status');
        });
    }

    /**
     * Remove the waitlist_position column.
     * Safe to run even with existing waitlisted rows (data loss is expected on rollback).
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('waitlist_position');
        });
    }
};
