<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    /**
     * Get all provinces
     *
     * @return \Illuminate\Http\Response;
     */
    public function getProvinces()
    {
        $provinces = Cache::rememberForever('provinces', function() {
            return Province::all();
        });

        return ResponseHelper::response(
            "Successfully get all provinces",
            200,
            ['provinces' => $provinces]
        );
    }

    /**
     * Get all regencies from a province
     *
     * @param  int $provinceId
     * @return Illuminate\Http\Response;
     */
    public function getRegencies(int $provinceId)
    {
        $province = Cache::rememberForever("regencies_$provinceId", function() use ($provinceId) {
            $province = Province::with('regencies:id,name,province_id')->find($provinceId, ['id', 'name']);
            if ($province) {
                foreach ($province->regencies as $regency) {
                    unset($regency->province_id);
                }
            }

            return $province;
        });

        if ($province == null) {
            return ResponseHelper::response("Not found", 404);
        }

        return ResponseHelper::response(
            "Successfully get all regencies in $province->name",
            200,
            ['province' => $province]
        );
    }

    /**
     * Get all districts from a regency
     *
     * @param  int $regencyId
     * @return Illuminate\Http\Response;
     */
    public function getDistricts(int $regencyId)
    {
        $regency = Cache::rememberForever("districts_$regencyId", function() use ($regencyId) {
            $regency = Regency::with('districts:id,name,regency_id')->find($regencyId, ['id', 'name']);

            if ($regency) {
                foreach ($regency->districts as $district) {
                    unset($district->regency_id);
                }
            }

            return $regency;
        });

        if ($regency == null) {
            return ResponseHelper::response("Not found", 404);
        }

        return ResponseHelper::response(
            "Successfully get all districts in $regency->name",
            200,
            ['regency' => $regency]
        );
    }
}
