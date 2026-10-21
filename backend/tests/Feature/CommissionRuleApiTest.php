<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class CommissionRuleApiTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'MotDePasse123';
    private const URL = '/api/v1/admin/commission-rules';

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
            'currency' => 'XAF',
            'type' => 'percentage',
            'percentage_bps' => 500,
        ], $override);
    }

    public function test_un_admin_cree_une_regle_par_defaut(): void
    {
        $this->withToken($this->tokenFor('admin'))->postJson(self::URL, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.supplier_id', null)
            ->assertJsonPath('data.percentage_bps', 500);
    }

    public function test_un_admin_cree_une_regle_pour_un_fournisseur(): void
    {
        $supplier = Supplier::factory()->create();

        $this->withToken($this->tokenFor('admin'))
            ->postJson(self::URL, $this->payload(['supplier_id' => $supplier->id]))
            ->assertCreated()
            ->assertJsonPath('data.supplier_id', $supplier->id);
    }

    public function test_une_commission_fixe_est_acceptee(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson(self::URL, $this->payload(['type' => 'fixed', 'percentage_bps' => null, 'fixed_minor' => 200]))
            ->assertCreated()
            ->assertJsonPath('data.fixed_minor', 200)
            ->assertJsonPath('data.percentage_bps', 0);
    }

    public function test_une_nouvelle_version_cloture_la_precedente(): void
    {
        $token = $this->tokenFor('admin');

        $first = $this->withToken($token)->postJson(self::URL, $this->payload())->assertCreated();

        $this->travel(1)->days();

        $this->withToken($token)->postJson(self::URL, $this->payload(['percentage_bps' => 600]))
            ->assertCreated()
            ->assertJsonPath('data.version', 2);

        $this->assertNotNull(CommissionRule::find($first->json('data.id'))->valid_until);
    }

    public function test_une_date_deffet_dans_le_passe_est_refusee(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson(self::URL, $this->payload(['valid_from' => now()->subDay()->toIso8601String()]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('valid_from');
    }

    public function test_des_valeurs_incoherentes_avec_le_type_sont_refusees(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson(self::URL, $this->payload(['fixed_minor' => 100]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_un_pourcentage_superieur_a_100_pour_cent_est_refuse(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->postJson(self::URL, $this->payload(['percentage_bps' => 10001]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('percentage_bps');
    }

    public function test_un_admin_retire_une_regle(): void
    {
        $rule = CommissionRule::factory()->create();

        $this->withToken($this->tokenFor('admin'))->postJson(self::URL.'/'.$rule->id.'/retire')
            ->assertOk()
            ->assertJsonPath('data.status', 'retired');
    }

    public function test_un_client_ne_peut_pas_gerer_les_regles(): void
    {
        $token = $this->tokenFor('client');

        $this->withToken($token)->getJson(self::URL)->assertForbidden();
        $this->withToken($token)->postJson(self::URL, $this->payload())->assertForbidden();
    }

    public function test_le_responsable_financier_peut_consulter_mais_pas_creer(): void
    {
        CommissionRule::factory()->create();
        $token = $this->tokenFor('finance');

        $this->withToken($token)->getJson(self::URL)->assertOk()->assertJsonCount(1, 'data');
        $this->withToken($token)->postJson(self::URL, $this->payload())->assertForbidden();
    }

    public function test_les_regles_exigent_un_jeton(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_une_regle_ne_peut_pas_etre_modifiee_apres_creation(): void
    {
        $rule = CommissionRule::factory()->create();

        $this->expectException(LogicException::class);

        $rule->update(['percentage_bps' => 1000]);
    }

    public function test_une_regle_ne_peut_pas_etre_supprimee(): void
    {
        $rule = CommissionRule::factory()->create();

        $this->expectException(LogicException::class);

        $rule->delete();
    }
}
