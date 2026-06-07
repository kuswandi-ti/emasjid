<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    /**
     * Default platform fee settings.
     *
     * platform_fee_percentage: 250 = 2.5% (basis poin)
     * platform_fee_mechanism: added_to_donor | deducted_from_donation
     * platform_fee_active: 1 = aktif, 0 = nonaktif
     */
    private array $settings = [
        [
            'key' => 'platform_fee_percentage',
            'value' => '250',
            'description' => 'Persentase fee platform dalam basis poin (250 = 2.5%)',
        ],
        [
            'key' => 'platform_fee_mechanism',
            'value' => 'added_to_donor',
            'description' => 'Mekanisme fee: added_to_donor (ditambahkan ke donatur) atau deducted_from_donation (dipotong dari donasi)',
        ],
        [
            'key' => 'platform_fee_active',
            'value' => '1',
            'description' => 'Status fee platform aktif (1) atau nonaktif (0)',
        ],
    ];

    public function run(): void
    {
        foreach ($this->settings as $setting) {
            PlatformSetting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                ]
            );
        }

        $this->command->info('Platform settings seeded successfully.');
    }
}
