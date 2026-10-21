<?php

namespace Tests\Feature;

use App\Models\CommissionRule;
use App\Models\Supplier;
use App\Services\Commission\CommissionCalculator;
use App\Services\Commission\NoCommissionRuleException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function rule(string $type, int $bps = 0, int $fixed = 0): CommissionRule
    {
        return new CommissionRule([
            'type' => $type,
            'percentage_bps' => $bps,
            'fixed_minor' => $fixed,
        ]);
    }

    public function test_exemple_du_cahier_des_charges_10000_xaf_a_5_pour_cent(): void
    {
        $b = (new CommissionCalculator())->calculate(10000, $this->rule('percentage', 500), 'XAF');

        $this->assertSame(500, $b->commissionMinor);
        $this->assertSame(9500, $b->supplierShareMinor);
        $this->assertSame(10000, $b->commissionMinor + $b->supplierShareMinor);
    }

    public function test_arrondi_a_lunite_la_plus_proche_0_5_vers_le_haut(): void
    {
        $calc = new CommissionCalculator();
        $rule = $this->rule('percentage', 500);

        $this->assertSame(1, $calc->calculate(10, $rule)->commissionMinor);   // 0,5 -> 1
        $this->assertSame(17, $calc->calculate(333, $rule)->commissionMinor); // 16,65 -> 17
        $this->assertSame(0, $calc->calculate(9, $rule)->commissionMinor);    // 0,45 -> 0
    }

    public function test_commission_fixe(): void
    {
        $b = (new CommissionCalculator())->calculate(10000, $this->rule('fixed', 0, 200));

        $this->assertSame(200, $b->commissionMinor);
        $this->assertSame(9800, $b->supplierShareMinor);
    }

    public function test_commission_combinee(): void
    {
        // 100 fixes + 2,5 % de 10 000 = 100 + 250
        $b = (new CommissionCalculator())->calculate(10000, $this->rule('combined', 250, 100));

        $this->assertSame(350, $b->commissionMinor);
        $this->assertSame(9650, $b->supplierShareMinor);
    }

    public function test_commission_en_kobo_pour_le_naira(): void
    {
        // 10 000 NGN = 1 000 000 kobo, commission de 1,5 %
        $b = (new CommissionCalculator())->calculate(1_000_000, $this->rule('percentage', 150), 'NGN');

        $this->assertSame(15000, $b->commissionMinor);
        $this->assertSame('NGN', $b->currency);
    }

    public function test_la_commission_ne_depasse_jamais_le_montant_paye(): void
    {
        $b = (new CommissionCalculator())->calculate(3000, $this->rule('fixed', 0, 5000));

        $this->assertSame(3000, $b->commissionMinor);
        $this->assertSame(0, $b->supplierShareMinor);
    }

    public function test_le_total_reparti_est_toujours_egal_au_montant_paye(): void
    {
        $calc = new CommissionCalculator();
        $rule = $this->rule('combined', 333, 7);

        foreach (range(1, 2000) as $amount) {
            $b = $calc->calculate($amount, $rule);

            $this->assertSame($amount, $b->commissionMinor + $b->supplierShareMinor);
            $this->assertGreaterThanOrEqual(0, $b->supplierShareMinor);
            $this->assertLessThanOrEqual($amount, $b->commissionMinor);
        }
    }

    public function test_un_montant_nul_ou_negatif_est_refuse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CommissionCalculator())->calculate(0, $this->rule('percentage', 500));
    }

    public function test_un_montant_trop_grand_est_refuse(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CommissionCalculator())->calculate(CommissionCalculator::MAX_AMOUNT_MINOR + 1, $this->rule('percentage', 500));
    }

    public function test_la_regle_du_fournisseur_prime_sur_la_regle_par_defaut(): void
    {
        $supplier = Supplier::factory()->create();
        CommissionRule::factory()->create(['percentage_bps' => 500]);
        CommissionRule::factory()->create(['supplier_id' => $supplier->id, 'percentage_bps' => 300]);

        $b = (new CommissionCalculator())->forSupplier(10000, 'XAF', $supplier);

        $this->assertSame(300, $b->commissionMinor);
    }

    public function test_la_regle_par_defaut_sapplique_sans_regle_fournisseur(): void
    {
        $supplier = Supplier::factory()->create();
        CommissionRule::factory()->create(['percentage_bps' => 500]);

        $b = (new CommissionCalculator())->forSupplier(10000, 'XAF', $supplier);

        $this->assertSame(500, $b->commissionMinor);
    }

    public function test_le_resultat_conserve_la_regle_et_sa_version(): void
    {
        $rule = CommissionRule::factory()->create(['version' => 3]);

        $b = (new CommissionCalculator())->forSupplier(10000, 'XAF');

        $this->assertSame($rule->id, $b->ruleId);
        $this->assertSame(3, $b->ruleVersion);
    }

    public function test_les_devises_sont_strictement_separees(): void
    {
        CommissionRule::factory()->create(['currency' => 'NGN']);

        $this->expectException(NoCommissionRuleException::class);

        (new CommissionCalculator())->forSupplier(10000, 'XAF');
    }

    public function test_une_regle_retiree_est_ignoree(): void
    {
        CommissionRule::factory()->create(['status' => 'retired']);

        $this->expectException(NoCommissionRuleException::class);

        (new CommissionCalculator())->forSupplier(10000, 'XAF');
    }

    public function test_une_regle_future_est_ignoree(): void
    {
        CommissionRule::factory()->create(['valid_from' => now()->addDay()]);

        $this->expectException(NoCommissionRuleException::class);

        (new CommissionCalculator())->forSupplier(10000, 'XAF');
    }

    public function test_une_regle_expiree_est_ignoree(): void
    {
        CommissionRule::factory()->create(['valid_until' => now()->subHour()]);

        $this->expectException(NoCommissionRuleException::class);

        (new CommissionCalculator())->forSupplier(10000, 'XAF');
    }

    public function test_la_regle_la_plus_recente_est_choisie(): void
    {
        CommissionRule::factory()->create(['percentage_bps' => 500, 'version' => 1, 'valid_from' => now()->subDays(10)]);
        CommissionRule::factory()->create(['percentage_bps' => 700, 'version' => 2, 'valid_from' => now()->subDay()]);

        $b = (new CommissionCalculator())->forSupplier(10000, 'XAF');

        $this->assertSame(700, $b->commissionMinor);
    }
}
