<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// Données de test LOCALES uniquement. Ne jamais exécuter en production.
class DemoSeeder extends Seeder
{
    private function makeUser(string $email, string $name, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'password' => Hash::make('MotDePasse123'),
            'role' => $role,
            'status' => 'active',
        ])->save();

        return $user;
    }

    public function run(): void
    {
        $this->makeUser('finance@example.test', 'Responsable Finance', 'finance');
        $this->makeUser('client@example.test', 'Client Demo', 'client');
        $supplierUser = $this->makeUser('fournisseur@example.test', 'Fournisseur Demo', 'supplier');

        $supplier = Supplier::firstOrNew(['business_name' => 'Fournisseur Demo']);
        $supplier->fill([
            'country_code' => 'CM',
            'category' => 'Services',
            'settlement_currency' => 'XAF',
            'contact_email' => 'fournisseur@example.test',
            'contact_phone' => '+237600000000',
            'owner_user_id' => $supplierUser->id,
        ]);
        $supplier->forceFill(['status' => 'active'])->save();

        if (! CommissionRule::whereNull('supplier_id')->where('currency', 'XAF')->exists()) {
            CommissionRule::create([
                'supplier_id' => null,
                'currency' => 'XAF',
                'type' => 'percentage',
                'percentage_bps' => 500,
                'fixed_minor' => 0,
                'valid_from' => now()->subDay(),
                'valid_until' => null,
                'version' => 1,
                'status' => 'active',
            ]);
        }
    }
}
