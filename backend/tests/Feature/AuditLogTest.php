<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'admin.audit@example.com',
            'password' => Hash::make('MotDePasse123'),
            'role' => 'admin',
            'status' => 'active',
        ])->save();

        return $user;
    }

    private function token(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'MotDePasse123',
        ])->assertOk()->json('token');
    }

    public function test_approuver_un_fournisseur_cree_une_entree_daudit(): void
    {
        $admin = $this->admin();
        $token = $this->token($admin);
        $supplier = Supplier::factory()->create(['status' => 'pending']);

        $this->withToken($token)->postJson("/api/v1/admin/suppliers/{$supplier->id}/approve")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'supplier.active',
            'auditable_type' => Supplier::class,
            'auditable_id' => $supplier->id,
        ]);
    }

    public function test_suspendre_un_fournisseur_cree_une_entree_daudit(): void
    {
        $admin = $this->admin();
        $token = $this->token($admin);
        $supplier = Supplier::factory()->create(['status' => 'active']);

        $this->withToken($token)->postJson("/api/v1/admin/suppliers/{$supplier->id}/suspend")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'supplier.suspended',
            'auditable_id' => $supplier->id,
        ]);
    }

    public function test_creer_une_regle_de_commission_cree_une_entree_daudit(): void
    {
        $admin = $this->admin();
        $token = $this->token($admin);

        $response = $this->withToken($token)->postJson('/api/v1/admin/commission-rules', [
            'type' => 'percentage',
            'percentage_bps' => 500,
            'currency' => 'XAF',
            'valid_from' => now()->addDay()->toDateString(),
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'commission_rule.created',
            'auditable_id' => $response->json('data.id'),
        ]);
    }

    public function test_retirer_une_regle_de_commission_cree_une_entree_daudit(): void
    {
        $admin = $this->admin();
        $token = $this->token($admin);

        $created = $this->withToken($token)->postJson('/api/v1/admin/commission-rules', [
            'type' => 'percentage',
            'percentage_bps' => 500,
            'currency' => 'XAF',
            'valid_from' => now()->addDay()->toDateString(),
        ])->assertCreated();

        $ruleId = $created->json('data.id');

        $this->withToken($token)->postJson("/api/v1/admin/commission-rules/{$ruleId}/retire")->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'commission_rule.retired',
            'auditable_id' => $ruleId,
        ]);
    }

    public function test_le_journal_daudit_ne_peut_etre_ni_modifie_ni_supprime(): void
    {
        $log = \App\Models\AuditLog::create([
            'action' => 'test.action',
            'auditable_type' => Supplier::class,
            'auditable_id' => 1,
        ]);

        $this->expectException(\LogicException::class);

        $log->update(['action' => 'autre']);
    }
}
