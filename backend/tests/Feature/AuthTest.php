<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse123';

    private function makeUser(string $role, string $status = 'active'): User
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Utilisateur '.$role,
            'email' => $role.'@example.com',
            'password' => Hash::make(self::PASSWORD),
            'role' => $role,
            'status' => $status,
        ])->save();

        return $user;
    }

    private function tokenFor(string $login): string
    {
        return $this->postJson('/api/v1/auth/login', [
            'login' => $login,
            'password' => self::PASSWORD,
        ])->assertOk()->json('token');
    }

    private function pingAdminAs(string $role): TestResponse
    {
        $this->makeUser($role);
        $token = $this->tokenFor($role.'@example.com');

        return $this->withToken($token)->getJson('/api/v1/admin/ping');
    }

    public function test_les_tests_utilisent_la_base_de_test(): void
    {
        $this->assertSame('app_paie_test', DB::connection()->getDatabaseName());
    }

    public function test_un_client_peut_sinscrire_et_recoit_un_jeton(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean',
            'email' => 'jean@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'client')
            ->assertJsonStructure(['token']);
    }

    public function test_linscription_ignore_le_role_envoye(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Pirate',
            'email' => 'pirate@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'role' => 'admin',
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'client');
    }

    public function test_un_mot_de_passe_faible_est_refuse(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean',
            'email' => 'jean@example.com',
            'password' => 'abc',
            'password_confirmation' => 'abc',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_un_email_ou_un_telephone_est_obligatoire(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Jean',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertUnprocessable();
    }

    public function test_inscription_et_connexion_par_telephone(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa',
            'phone' => '+237670000000',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'login' => '+237670000000',
            'password' => self::PASSWORD,
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_un_mauvais_mot_de_passe_est_refuse(): void
    {
        $this->makeUser('client');

        $this->postJson('/api/v1/auth/login', [
            'login' => 'client@example.com',
            'password' => 'MauvaisMotDePasse1',
        ])->assertUnauthorized();
    }

    public function test_un_compte_suspendu_ne_peut_pas_se_connecter(): void
    {
        $this->makeUser('client', 'suspended');

        $this->postJson('/api/v1/auth/login', [
            'login' => 'client@example.com',
            'password' => self::PASSWORD,
        ])->assertForbidden();
    }

    public function test_me_exige_un_jeton(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_retourne_lutilisateur_connecte(): void
    {
        $this->makeUser('client');
        $token = $this->tokenFor('client@example.com');

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'client@example.com')
            ->assertJsonPath('user.role', 'client');
    }

    public function test_un_client_ne_peut_pas_acceder_a_la_zone_admin(): void
    {
        $this->pingAdminAs('client')->assertForbidden();
    }

    public function test_un_fournisseur_ne_peut_pas_acceder_a_la_zone_admin(): void
    {
        $this->pingAdminAs('supplier')->assertForbidden();
    }

    public function test_un_responsable_financier_ne_peut_pas_acceder_a_la_zone_admin(): void
    {
        $this->pingAdminAs('finance')->assertForbidden();
    }

    public function test_un_administrateur_accede_a_la_zone_admin(): void
    {
        $this->pingAdminAs('admin')->assertOk()->assertJsonPath('message', 'admin ok');
    }

    public function test_la_zone_admin_exige_un_jeton(): void
    {
        $this->getJson('/api/v1/admin/ping')->assertUnauthorized();
    }

    public function test_un_jeton_existant_est_refuse_apres_suspension_du_compte(): void
    {
        $user = $this->makeUser('client');
        $token = $this->tokenFor('client@example.com');

        $user->forceFill(['status' => 'suspended'])->save();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_la_deconnexion_supprime_le_jeton(): void
    {
        $this->makeUser('client');
        $token = $this->tokenFor('client@example.com');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}