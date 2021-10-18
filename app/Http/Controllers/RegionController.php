<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    public function getProvinces()
    {
        $provinces = Cache::rememberForever('provinces', function() {
            return Province::all();
        });

        return ResponseHelper::response(
            "Successfully get all provinces",
            ['provinces' => $provinces],
            200
        );
    }

    public function getRegencies($provinceId)
    {
        $province = Cache::rememberForever("regencies_$provinceId", function() use ($provinceId) {
            $province = Province::with('regencies:id,name,province_id')->findOrFail($provinceId, ['id', 'name']);
            foreach ($province->regencies as $regency) {
                unset($regency->province_id);
            }

            return $province;
        });

        return ResponseHelper::response(
            "Successfully get all regencies in $province->name",
            ['province' => $province],
            200
        );
    }

    public function getDistricts($regencyId)
    {
        $regency = Cache::rememberForever("districts_$regencyId", function() use ($regencyId) {
            $regency = Regency::with('districts:id,name,regency_id')->findOrFail($regencyId, ['id', 'name']);
            foreach ($regency->districts as $district) {
                unset($district->regency_id);
            }

            return $regency;
        });

        return ResponseHelper::response(
            "Successfully get all districts in $regency->name",
            ['regency' => $regency],
            200
        );
    }
}
