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
        $provinces = Http::get("http://www.emsifa.com/api-wilayah-indonesia/api/provinces.json")->json();
        foreach ($provinces as $province) {
            $provinceName = $province['name'];
            $this->command->info("Creating database for province $provinceName");
            Province::create($province);
            $provinceId = $province['id'];

            $regencies = Http::get("https://emsifa.github.io/api-wilayah-indonesia/api/regencies/$provinceId.json")->json();
            foreach ($regencies as $regency) {
                $regencyName = $regency['name'];
                $this->command->info("  Creating database for regency $regencyName");
                Regency::create($regency);
                $regencyId = $regency['id'];

                $districts = Http::get("https://emsifa.github.io/api-wilayah-indonesia/api/districts/$regencyId.json")->json();
                foreach ($districts as $district) {
                    $districtName = $district['name'];
                    $this->command->info("      Creating database for district $districtName");
                    District::create($district);
                }
            }
        }
    }
}
