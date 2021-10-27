<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Seeder;

class LatLongProvinceSeeder extends Seeder
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
            $oldProvince = Province::find($province['id']);
            $oldProvince->latitude = $province['latitude'];
            $oldProvince->longitude = $province['longitude'];
            $oldProvince->alt_name = $province['alt_name'];

            $oldProvince->update();
        }
    }
}
