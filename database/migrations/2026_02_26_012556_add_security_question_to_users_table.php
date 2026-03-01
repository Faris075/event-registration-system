<?php

// =============================================================================
// Migration: add_security_question_to_users_table
// Supports the custom password-recovery flow implemented in
// SecurityQuestionController. Users who have set a security question can
// recover access without relying on email delivery (Breeze's default path).
//
// Security notes:
//   - security_answer is stored as a bcrypt HASH (Hash::make), not plaintext.
//   - The answer is normalised (strtolower + trim) before hashing and before
//     comparison, so casing/whitespace differences don't cause false failures.
//   - Both columns are nullable so that existing users are not blocked from
//     logging in; EnsureSecurityQuestion middleware will prompt them to set one.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add security question + answer columns to users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The question text chosen by the user (e.g. "What was your first pet's name?").
            // nullable: users without a question are handled gracefully by middleware.
            $table->string('security_question')->nullable()->after('is_admin');

            // bcrypt hash of the normalised answer — never store plain-text secrets.
            // nullable: set at the same time as security_question; both are NULL until setup.
            $table->string('security_answer')->nullable()->after('security_question');
        });
    }

    /**
     * Drop both columns together to keep schema clean on rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pass an array to dropColumn to remove both in a single ALTER TABLE statement.
            $table->dropColumn(['security_question', 'security_answer']);
        });
    }
};
