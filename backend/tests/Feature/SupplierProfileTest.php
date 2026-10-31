<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierProfileTest extends TestCase
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

    private function login(User $user): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'MotDePasse123',
        ])->assertOk()->json('token');
    }

    public function test_un_fournisseur_consulte_son_propre_profil(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);
        $other = Supplier::factory()->create(['status' => 'active']);

        $user = $this->makeUser('supplier', 'profil@example.com');
        $supplier->update(['owner_user_id' => $user->id]);

        $this->withToken($this->login($user))
            ->getJson('/api/v1/supplier/profile')
            ->assertOk()
            ->assertJsonPath('data.business_name', $supplier->business_name)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonMissingPath('data.owner_user_id')
            ->assertJsonMissing(['business_name' => $other->business_name]);
    }

    public function test_un_utilisateur_sans_fournisseur_lie_recoit_404(): void
    {
        $user = $this->makeUser('supplier', 'orphelin.profil@example.com');

        $this->withToken($this->login($user))
            ->getJson('/api/v1/supplier/profile')
            ->assertNotFound();
    }

    public function test_un_client_ne_peut_pas_lire_le_profil_fournisseur(): void
    {
        $client = $this->makeUser('client', 'client.profil@example.com');

        $this->withToken($this->login($client))
            ->getJson('/api/v1/supplier/profile')
            ->assertForbidden();
    }
}
