<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ExpenseCategoriesTableSeeder;
use Database\Seeders\FinanceMenusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Module 6 — expenses.
 *
 * Expenses are org-scoped (suppliers pattern), categorized by the
 * expense_categories lookup, with a `paid` boolean as the only
 * accounts-payable state (ADR-0007) — toggled via PUT /expenses/{id}/paid.
 */
class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('roles')->insert([
            ['id' => Role::NOXUS_ROLE, 'name' => 'Noxus', 'status' => 1],
            ['id' => Role::ADMIN_ROLE, 'name' => 'Admin', 'status' => 1],
        ]);

        $this->seed(FinanceMenusSeeder::class);
        $this->seed(ExpenseCategoriesTableSeeder::class);
    }

    public function test_store_creates_expense_scoped_to_the_users_org(): void
    {
        [$org, $admin] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/expenses', [
                'expense_category_id' => $this->categoryId('Veterinário'),
                'description' => 'Consulta de emergência',
                'amount' => 250.75,
                'expense_date' => '2026-07-10',
            ])
            ->assertCreated()
            ->assertJsonPath('data.description', 'Consulta de emergência')
            ->assertJsonPath('data.category.name', 'Veterinário')
            ->assertJsonPath('data.paid', false);

        $this->assertDatabaseHas('expenses', [
            'description' => 'Consulta de emergência',
            'organization_id' => $org->id,
            'amount' => 250.75,
            'paid' => 0,
        ]);
    }

    public function test_organization_id_from_request_body_is_ignored(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->actingAs($admin, 'api')
            ->postJson('/api/expenses', [
                'description' => 'Ração',
                'amount' => 100,
                'expense_date' => '2026-07-10',
                'organization_id' => $orgB->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('expenses', [
            'description' => 'Ração',
            'organization_id' => $org->id,
        ]);
    }

    public function test_index_lists_only_own_orgs_expenses(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $this->makeExpense($org, 'Minha Despesa');
        $this->makeExpense($orgB, 'Despesa Alheia');

        $this->actingAs($admin, 'api')
            ->getJson('/api/expenses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Minha Despesa');
    }

    public function test_update_edits_expense(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $expense = $this->makeExpense($org, 'Ração');

        $this->actingAs($admin, 'api')
            ->putJson("/api/expenses/{$expense->id}", [
                'description' => 'Ração premium',
                'amount' => 199.90,
                'expense_date' => '2026-07-12',
                'paid' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Ração premium',
            'amount' => 199.90,
            'paid' => 1,
        ]);
    }

    public function test_toggle_paid_flips_the_flag(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $expense = $this->makeExpense($org, 'Conta de luz');

        $this->actingAs($admin, 'api')
            ->putJson("/api/expenses/{$expense->id}/paid")
            ->assertOk();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'paid' => 1]);

        $this->actingAs($admin, 'api')
            ->putJson("/api/expenses/{$expense->id}/paid")
            ->assertOk();

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'paid' => 0]);
    }

    public function test_destroy_soft_deletes_expense(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        $expense = $this->makeExpense($org, 'Ração');

        $this->actingAs($admin, 'api')
            ->deleteJson("/api/expenses/{$expense->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_expense_from_another_org_is_not_reachable(): void
    {
        [, $adminA] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        $theirs = $this->makeExpense($orgB, 'Alheia');

        $this->actingAs($adminA, 'api')
            ->getJson("/api/expenses/{$theirs->id}")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->putJson("/api/expenses/{$theirs->id}/paid")
            ->assertNotFound();

        $this->actingAs($adminA, 'api')
            ->deleteJson("/api/expenses/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_expense_categories_lookup_returns_defaults_plus_own_customs_only(): void
    {
        [$org, $admin] = $this->orgWithAdmin();
        [$orgB] = $this->orgWithAdmin();

        ExpenseCategory::create(['organization_id' => $org->id, 'name' => 'Minha Categoria']);
        ExpenseCategory::create(['organization_id' => $orgB->id, 'name' => 'Categoria Alheia']);

        $response = $this->actingAs($admin, 'api')
            ->getJson('/api/expense-categories')
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Veterinário'));
        $this->assertTrue($names->contains('Minha Categoria'));
        $this->assertFalse($names->contains('Categoria Alheia'));
    }

    // ---- helpers ---------------------------------------------------------

    /**
     * @return array{0: Organization, 1: User}
     */
    private function orgWithAdmin(): array
    {
        $org = Organization::create([
            'name' => 'Org '.Str::random(6),
            'tax_id' => (string) Str::uuid(),
        ]);

        $admin = User::create([
            'organization_id' => $org->id,
            'name' => 'User '.Str::random(6),
            'email' => Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'role_id' => Role::ADMIN_ROLE,
        ]);

        return [$org, $admin];
    }

    private function categoryId(string $name): int
    {
        return ExpenseCategory::whereNull('organization_id')->where('name', $name)->value('id');
    }

    private function makeExpense(Organization $org, string $description): Expense
    {
        return Expense::create([
            'organization_id' => $org->id,
            'description' => $description,
            'amount' => 100,
            'expense_date' => '2026-07-01',
        ]);
    }
}
