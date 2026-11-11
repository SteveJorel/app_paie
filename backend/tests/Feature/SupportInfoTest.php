<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupportInfoTest extends TestCase
{
    public function test_les_coordonnees_du_support_sont_publiques(): void
    {
        config([
            'receipts.company_name' => 'Ma Plateforme',
            'receipts.support_email' => 'aide@exemple.com',
            'receipts.support_phone' => '+237600000000',
        ]);

        $this->getJson('/api/v1/support')
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Ma Plateforme')
            ->assertJsonPath('data.support_email', 'aide@exemple.com')
            ->assertJsonPath('data.support_phone', '+237600000000');
    }
}
