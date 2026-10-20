<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse123';

    private function tokenFor(string $role): string
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Utilisateur '.$role,
            'email' => $role.'@example.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => 'active',
        ])->save();

        return $this->postJson('/api/v1/auth/login', [
            'login' => $role.'@example.com',
            'password' => self::PASSWORD,
        ])->assertOk()->json('token');
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'business_name' => 'Boulangerie Sahel',
            'country_code' => 'CM',
            'category' => 'alimentation',
            'settlement_currency' => 'XAF',
        ], $override);
    }

    public function test_la_liste_des_fournisseurs_exige_un_jeton(): void
    {
        $this->getJson('/api/v1/suppliers')->assertUnauthorized();
    }

    public function test_la_liste_publique_ne_contient_que_les_fournisseurs_actifs(): void
    {
        Supplier::factory()->create(['business_name' => 'Actif SARL']);
        Supplier::factory()->create(['business_name' => 'En attente SARL', 'status' => 'pending']);
        Supplier::factory()->create(['business_name' => 'Suspendu SARL', 'status' => 'suspended']);

        $this->withToken($this->tokenFor('client'))->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.business_name', 'Actif SARL')
            ->assertJsonMissingPath('data.0.status')
            ->assertJsonMissingPath('data.0.contact_email');
    }

    public function test_un_client_voit_le_detail_dun_fournisseur_actif(): void
    {
        $supplier = Supplier::factory()->create();

        $this->withToken($this->tokenFor('client'))->getJson('/api/v1/suppliers/'.$supplier->id)
            ->assertOk()
            ->assertJsonPath('data.business_name', $supplier->business_name);
    }

    public function test_un_client_ne_voit_pas_le_detail_dun_fournisseur_non_actif(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'pending']);

        $this->withToken($this->tokenFor('client'))->getJson('/api/v1/suppliers/'.$supplier->id)
            ->assertNotFound();
    }

    public function test_un_client_ne_peut_pas_creer_un_fournisseur(): void
    {
        $this->withToken($this->tokenFor('client'))->postJson('/api/v1/admin/suppliers', $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('suppliers', 0);
    }

    public function test_un_admin_cree_un_fournisseur_en_attente_meme_si_un_statut_est_envoye(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson('/api/v1/admin/suppliers', $this->payload(['status' => 'active']))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('suppliers', ['business_name' => 'Boulangerie Sahel', 'status' => 'pending']);
    }

    public function test_un_admin_valide_puis_suspend_un_fournisseur(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'pending']);
        $token = $this->tokenFor('admin');

        $this->withToken($token)->postJson("/api/v1/admin/suppliers/{$supplier->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->withToken($token)->postJson("/api/v1/admin/suppliers/{$supplier->id}/suspend")
            ->assertOk()
            ->assertJsonPath('data.status', 'suspended');
    }

    public function test_un_admin_modifie_un_fournisseur(): void
    {
        $supplier = Supplier::factory()->create();

        $this->withToken($this->tokenFor('admin'))
            ->patchJson("/api/v1/admin/suppliers/{$supplier->id}", ['business_name' => 'Nouveau Nom'])
            ->assertOk()
            ->assertJsonPath('data.business_name', 'Nouveau Nom');
    }

    public function test_un_pays_non_pris_en_charge_est_refuse(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson('/api/v1/admin/suppliers', $this->payload(['country_code' => 'FR']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('country_code');
    }

    public function test_ladmin_peut_filtrer_les_fournisseurs_par_statut(): void
    {
        Supplier::factory()->count(2)->create(['status' => 'pending']);
        Supplier::factory()->create();

        $this->withToken($this->tokenFor('admin'))->getJson('/api/v1/admin/suppliers?status=pending')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}