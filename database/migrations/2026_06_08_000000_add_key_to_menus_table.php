<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add nullable first so the column can be added to tables that already have rows.
        Schema::table('menus', function (Blueprint $table) {
            $table->string('key')->nullable()->after('name');
        });

        // 2. Backfill the seeded menus on pre-existing databases.
        $keys = [
            'Administrativo' => 'administrative',
            'Usuários'       => 'users',
            'Perfis'         => 'roles',
            'Menus'          => 'menus',
            'Comercial'      => 'commercial',
            'Clientes'       => 'customers',
            'Fornecedores'   => 'suppliers',
        ];

        foreach ($keys as $name => $key) {
            DB::table('menus')->where('name', $name)->whereNull('key')->update(['key' => $key]);
        }

        // 3. Safety net for any remaining rows (e.g. UI-created menus) so NOT NULL can be applied.
        DB::table('menus')->whereNull('key')->update(['key' => DB::raw("CONCAT('menu_', id)")]);

        // 4. Enforce NOT NULL via raw SQL (doctrine/dbal not installed; `key` is a reserved word).
        DB::statement('ALTER TABLE `menus` MODIFY `key` VARCHAR(255) NOT NULL');

        // 5. Unique key for stable permission lookups.
        Schema::table('menus', function (Blueprint $table) {
            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->dropColumn('key');
        });
    }
};
