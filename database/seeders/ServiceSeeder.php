<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $serviceList = [
            [
                'name' => 'CLASSICO',
                // 'amount' => '280'
            ],
            [
                'name' => 'SIGNATURE',
                // 'amount' => '380'
            ],
            [
                'name' => 'DELUX',
                // 'amount' => '750'
            ],
            [
                'name' => 'PACKAGE',
                // 'amount' => '500'
            ],
            [
                'name' => 'PACKAGE2',
                // 'amount' => '850'
            ],
            [
                'name' => 'PACKAGE3',
                // 'amount' => '1400'
            ],
            [
                'name' => 'BEARD SHAVE',
                // 'amount' => '200'
            ],
            [
                'name' => 'BEARD SHAPING',
                // 'amount' => '200'
            ],
            [
                'name' => 'BEARD SCULPTING',
                // 'amount' => '200'
            ],
            [
                'name' => 'MUSTACHE',
                // 'amount' => '170'
            ],
            [
                'name' => 'BEARD TRIM',
                // 'amount' => '170'
            ],
            [
                'name' => 'HAIR SPA',
                // 'amount' => '600'
            ],
            [
                'name' => 'HAIR AND SCALP TREATMENT',
                // 'amount' => '170'
            ],
            [
                'name' => 'HAIR COLOR',
                // 'amount' => '170'
            ],
        ];
        $services = Service::insert($serviceList);
    }
}
