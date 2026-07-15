<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the "Sistema" sidebar group with its "Documentos" and "Auditoria"
 * children (Module 7 — cross-cutting).
 *
 * Both are organization-scoped: documents are polymorphic file attachments
 * (ADR-0005) and audit logs are the append-only activity trail. The Admin
 * role gets full access to documents but view-only on the audit trail —
 * audit_logs has no write surface at all (Noxus bypasses the permission
 * check). Idempotent; depends on RolesTableSeeder.
 */
class SystemMenusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Container menu (no route) — the "Sistema" group.
        DB::table('menus')->updateOrInsert(
            ['name' => 'Sistema', 'parent_id' => null],
            [
                'route' => null,
                'icon' => 'admin_panel_settings',
                'order' => 4,
                'status' => 1,
                'exclusive_noxus' => 0,
                'updated_at' => $now,
                'created_at' => $now,
            ],
        );

        $parentId = DB::table('menus')
            ->whereNull('parent_id')
            ->where('name', 'Sistema')
            ->value('id');

        $children = [
            ['name' => 'Documentos', 'route' => '/documents', 'icon' => 'description', 'order' => 1, 'can_create' => 1, 'can_update' => 1],
            ['name' => 'Auditoria', 'route' => '/audit-logs', 'icon' => 'history', 'order' => 2, 'can_create' => 0, 'can_update' => 0],
        ];

        foreach ($children as $child) {
            DB::table('menus')->updateOrInsert(
                ['route' => $child['route']],
                [
                    'name' => $child['name'],
                    'icon' => $child['icon'],
                    'parent_id' => $parentId,
                    'order' => $child['order'],
                    'status' => 1,
                    'exclusive_noxus' => 0,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $menuId = DB::table('menus')->where('route', $child['route'])->value('id');

            // Admin gets the grants above; every other non-Noxus role starts with none.
            DB::table('role_menu')->updateOrInsert(
                ['role_id' => Role::ADMIN_ROLE, 'menu_id' => $menuId],
                [
                    'can_view' => 1,
                    'can_create' => $child['can_create'],
                    'can_update' => $child['can_update'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
