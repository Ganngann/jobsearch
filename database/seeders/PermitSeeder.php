<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permits = [
            ['slug' => 'am', 'label' => 'Permis AM (Cyclomoteur)', 'code' => 'AM'],
            ['slug' => 'a', 'label' => 'Permis A (Moto)', 'code' => 'A'],
            ['slug' => 'a1', 'label' => 'Permis A1 (Moto légère)', 'code' => 'A1'],
            ['slug' => 'a2', 'label' => 'Permis A2 (Moto moyenne)', 'code' => 'A2'],
            ['slug' => 'b', 'label' => 'Permis B (Voiture)', 'code' => 'B'],
            ['slug' => 'be', 'label' => 'Permis BE (Voiture + Remorque)', 'code' => 'BE'],
            ['slug' => 'c', 'label' => 'Permis C (Poids lourd)', 'code' => 'C'],
            ['slug' => 'ce', 'label' => 'Permis CE (Poids lourd + Remorque)', 'code' => 'CE'],
            ['slug' => 'd', 'label' => 'Permis D (Autocar)', 'code' => 'D'],
            ['slug' => 'g', 'label' => 'Permis G (Tracteur agricole)', 'code' => 'G'],
        ];

        foreach ($permits as $permit) {
            \App\Models\Permit::updateOrCreate(
                ['slug' => $permit['slug']],
                ['label' => $permit['label'], 'code' => $permit['code'], 'value' => $permit['code']]
            );
        }
    }
}
