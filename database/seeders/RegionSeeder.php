<?php

namespace Database\Seeders;

use App\Imports\ProvinceImport;
use App\Imports\RegencyImport;
use App\Imports\RegionImport;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;

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
            Province::create([
                'id' => $province['id'],
                'name' => $province['name'],
                'latitude' => $province['latitude'],
                'longitude' => $province['longitude'],
            ]);
        }

        Excel::import(new ProvinceImport, base_path('resources/data/apiProvinsi.csv'));
        Excel::import(new RegencyImport, base_path('resources/data/apiKabupaten.csv'));
    }
}
