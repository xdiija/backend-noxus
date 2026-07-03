<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Permissions are now matched on the menu route (without the leading slash),
    // so the separate `key` column is redundant. See User::hasPermission().
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('key')->nullable()->after('name');
        });

        // Best-effort restore: derive the key from the route for leaf menus.
        DB::table('menus')
            ->whereNotNull('route')
            ->update(['key' => DB::raw("TRIM(LEADING '/' FROM route)")]);

        Schema::table('menus', function (Blueprint $table) {
            $table->unique('key');
        });
    }
};
