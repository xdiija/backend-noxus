<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Multi-tenancy: scope suppliers to an organization and make CNPJ/email
    // unique per organization instead of globally. Nullable so pre-existing
    // rows survive the migration; new suppliers get their org from the
    // authenticated user (set in SupplierService, never from request input).
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();

            // Global uniqueness -> per-organization uniqueness.
            $table->dropUnique(['cnpj']);
            $table->dropUnique(['email']);
            $table->unique(['organization_id', 'cnpj']);
            $table->unique(['organization_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Drop the FK first: MySQL uses one of the composite unique indexes
            // to back the organization_id foreign key and refuses to drop it
            // while the constraint still exists (error 1553).
            $table->dropForeign(['organization_id']);
            $table->dropUnique(['organization_id', 'cnpj']);
            $table->dropUnique(['organization_id', 'email']);
            $table->dropColumn('organization_id');
            $table->unique('cnpj');
            $table->unique('email');
        });
    }
};
