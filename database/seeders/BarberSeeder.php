<?php

namespace Database\Seeders;

use App\Models\Barber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BarberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $barberList = [
            [
                'name' => 'OWEN',
                'position' => 'barber',
                'rate' => 0
            ],
            [
                'name' => 'JAY',
                'position' => 'barber',
                'rate' => 0
            ],
            [
                'name' => 'VINCE',
                'position' => 'barber',
                'rate' => 0
            ],
        ];

        $barbers = Barber::insert($barberList);
    }
}
