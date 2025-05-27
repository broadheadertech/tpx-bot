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
            ['name' => 'owen'],
            ['name' => 'jay'],
            ['name' => 'vince'],
        ];

        $barbers = Barber::insert($barberList);
    }
}
