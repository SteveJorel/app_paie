<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlatformConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformConfigSeeder::class);
    }

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

    public function test_aucun_moyen_nest_annonce_tant_quil_nest_pas_actif(): void
    {
        $client = $this->makeUser('client', 'client.platform@example.com');
        $token = $this->token($client);

        $this->withToken($token)->getJson('/api/v1/payment-methods?country=CM')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_un_admin_active_un_moyen_qui_devient_visible_pour_le_client(): void
    {
        $admin = $this->makeUser('admin', 'admin.platform@example.com');
        $adminToken = $this->token($admin);

        $this->withToken($adminToken)->patchJson('/api/v1/admin/payment-methods/CM/mobile_money', [
            'status' => 'active',
        ])->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('audit_logs', ['action' => 'payment_method.availability_updated']);

        $client = $this->makeUser('client', 'client.platform2@example.com');
        $clientToken = $this->token($client);

        $this->withToken($clientToken)->getJson('/api/v1/payment-methods?country=CM')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'mobile_money');
    }

    public function test_un_client_ne_peut_pas_modifier_la_disponibilite(): void
    {
        $client = $this->makeUser('client', 'client.platform3@example.com');
        $token = $this->token($client);

        $this->withToken($token)->patchJson('/api/v1/admin/payment-methods/CM/mobile_money', [
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_un_code_pays_invalide_est_refuse(): void
    {
        $client = $this->makeUser('client', 'client.platform4@example.com');
        $token = $this->token($client);

        $this->withToken($token)->getJson('/api/v1/payment-methods?country=ZZ')
            ->assertStatus(422);
    }
}
