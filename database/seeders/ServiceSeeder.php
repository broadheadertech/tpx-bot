<?php

namespace Database\Seeders;

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
                'name' => 'classico',
                // 'amount' => '280'
            ],
            [
                'name' => 'signature',
                // 'amount' => '380'
            ],
            [
                'name' => 'deluxe',
                // 'amount' => '750'
            ],
            [
                'name' => 'package1',
                // 'amount' => '500'
            ],
            [
                'name' => 'package2',
                // 'amount' => '850'
            ],
            [
                'name' => 'package3',
                // 'amount' => '1400'
            ],
            [
                'name' => 'beard_shave',
                // 'amount' => '200'
            ],
            [
                'name' => 'beard_shaping',
                // 'amount' => '200'
            ],
            [
                'name' => 'beard_sculpting',
                // 'amount' => '200'
            ],
            [
                'name' => 'mustache',
                // 'amount' => '170'
            ],
            [
                'name' => 'beard_trim',
                // 'amount' => '170'
            ],
            [
                'name' => 'hair_spa',
                // 'amount' => '600'
            ],
            [
                'name' => 'hair_and_scalp_treatment',
                // 'amount' => '170'
            ],
            [
                'name' => 'hair_color',
                // 'amount' => '170'
            ],

        ];
    }
}
