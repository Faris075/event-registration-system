<?php

// =============================================================================
// Migration: add_currency_to_users_table
// Stores each user's preferred display currency so event prices can be shown
// in a currency familiar to the user.
//
// Supported values are defined as constants in the User model (User::CURRENCIES):
//   USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY, INR, MXN
//
// The actual conversion is performed in User::convertPrice() and
// User::getCurrencySymbolAttribute() — the DB stores only the ISO code.
// =============================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add currency column to users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // VARCHAR(10) is more than enough for a 3-char ISO 4217 currency code.
            // default('USD'): existing users get USD, the base currency used in stored prices.
            // after('is_admin'): grouped with other user-preference columns.
            $table->string('currency', 10)->default('USD')->after('is_admin');
        });
    }

    /**
     * Remove the currency column.
     * Existing users will revert to system default (USD) after rollback.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
