<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => Hash::make('MotDePasse123'),
            'role' => $role,
            'status' => 'active',
        ])->save();

        return $user;
    }

    private function token(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email, 'password' => 'MotDePasse123',
        ])->assertOk()->json('token');
    }

    public function test_un_admin_consulte_le_journal_daudit_filtre_par_action(): void
    {
        $admin = $this->makeUser('admin', 'admin.audit.list@example.com');
        $token = $this->token($admin);

        $supplier = Supplier::factory()->create(['status' => 'pending']);
        $this->withToken($token)->postJson("/api/v1/admin/suppliers/{$supplier->id}/approve")->assertOk();

        $this->withToken($token)->getJson('/api/v1/admin/audit-logs?action=supplier.active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.auditable_id', $supplier->id);
    }

    public function test_le_responsable_financier_ne_peut_pas_consulter_le_journal_daudit(): void
    {
        $finance = $this->makeUser('finance', 'finance.audit@example.com');
        $token = $this->token($finance);

        $this->withToken($token)->getJson('/api/v1/admin/audit-logs')->assertForbidden();
    }

    public function test_un_client_ne_peut_pas_consulter_le_journal_daudit(): void
    {
        $client = $this->makeUser('client', 'client.audit@example.com');
        $token = $this->token($client);

        $this->withToken($token)->getJson('/api/v1/admin/audit-logs')->assertForbidden();
    }
}
