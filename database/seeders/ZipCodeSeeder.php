<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZipCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('belgian_zipcodes.csv');
        if (!file_exists($filePath)) return;

        $handle = fopen($filePath, 'r');
        $dataToInsert = [];
        $now = now();

        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            $dataToInsert[] = [
                'zip_code' => $data[0],
                'city' => $data[1],
                'longitude' => $data[2],
                'latitude' => $data[3],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in chunks of 500
            if (count($dataToInsert) >= 500) {
                \App\Models\ZipCode::insert($dataToInsert);
                $dataToInsert = [];
            }
        }

        if (count($dataToInsert) > 0) {
            \App\Models\ZipCode::insert($dataToInsert);
        }

        fclose($handle);
    }
}
