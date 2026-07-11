<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promotes `breeds` from global reference data to a per-org configurable lookup
 * (ADR-0004): NULL organization_id = system default shared by all orgs; a
 * non-null value = a custom breed owned by one organization. Uniqueness moves
 * to a per-org composite so each org can add its own breeds without colliding
 * with the shared defaults or other tenants.
 *
 * The new unique keeps `species_id` as its leftmost column on purpose: the
 * `species_id` foreign key leans on that index, so it must stay indexed when
 * the old `(species_id, name)` unique is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('breeds', function (Blueprint $table) {
            // null = system default, available to every organization
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            $table->unique(['species_id', 'name', 'organization_id']);
            $table->dropUnique(['species_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('breeds', function (Blueprint $table) {
            $table->unique(['species_id', 'name']);
            $table->dropUnique(['species_id', 'name', 'organization_id']);
            $table->dropConstrainedForeignId('organization_id');
        });
    }
};
