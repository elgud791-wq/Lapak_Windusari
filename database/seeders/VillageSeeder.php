<?php

namespace Database\Seeders;

use App\Models\Village;
use Illuminate\Database\Seeder;

class VillageSeeder extends Seeder
{
    public function run(): void
    {
        $villages = [
            'Balesari', 'Bandarsedayu', 'Banjarsari', 'Candisari', 'Dampit',
            'Genito', 'Girimulyo', 'Gondangrejo', 'Gunungsari', 'Kalijoso',
            'Kembangkuning', 'Kentengsari', 'Mangunsari', 'Ngemplak', 'Pasangsari',
            'Semen', 'Tanjungsari', 'Umbulsari', 'Windusari', 'Wonoroto',
        ];

        foreach ($villages as $village) {
            Village::create([
                'name' => $village,
                'code' => null,
            ]);
        }
    }
}
