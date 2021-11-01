<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $jsonString = file_get_contents(base_path('resources/data/ProvinsiGeoJSON.json'));
        $provinces = json_decode($jsonString, true);

        foreach ($provinces as $province) {
            $provinceName = $province['name'];
            $this->command->info("Creating database for province $provinceName");
            Province::create([
                'id' => $province['id'],
                'name' => $province['name'],
                'latitude' => $province['latitude'],
                'longitude' => $province['longitude'],
                'alt_name' => $province['alt_name'],
            ]);
            $provinceId = $province['id'];

            $regencies = Http::get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/$provinceId.json")->json();
            foreach ($regencies as $regency) {
                $regencyName = $regency['name'];
                $this->command->info("  Creating database for regency $regencyName");
                Regency::create($regency);
            }
        }
    }
}
