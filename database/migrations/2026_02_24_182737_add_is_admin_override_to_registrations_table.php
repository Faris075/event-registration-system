<?php

// =============================================================================
// Migration: add_is_admin_override_to_registrations_table
// Marks registrations that were force-added by an admin beyond normal capacity.
//
// Business rule:
//   Admins may bypass the capacity limit (adminForceAdd in
//   EventRegistrationController) up to a maximum of 5 override registrations
//   per event. This flag distinguishes those exceptional rows from standard
//   confirmed registrations so capacity counts remain accurate.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add is_admin_override boolean column.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // boolean() maps to TINYINT(1) in MySQL.
            // default(false) = all existing rows are retroactively set to 0 (standard registrations).
            // after('payment_status'): logically grouped with other status-like columns.
            $table->boolean('is_admin_override')->default(false)->after('payment_status');
        });
    }

    /**
     * Remove the is_admin_override column.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('is_admin_override');
        });
    }
};
