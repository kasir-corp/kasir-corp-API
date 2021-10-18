<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Regency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    public function getProvinces()
    {
        $provinces = Cache::remember('provinces', 600, function() {
            return Province::all();
        });

        return $provinces;
    }

    public function getRegencies($provinceId)
    {
        $regencies = Cache::remember("regencies_$provinceId", 600, function() use ($provinceId) {
            return Province::with('regencies')->findOrFail($provinceId);
        });

        return $regencies;
    }

    public function getDistricts($regencyId)
    {
        $districts = Cache::remember("districts_$regencyId", 600, function() use ($regencyId) {
            return Regency::with('districts')->findOrFail($regencyId);
        });

        return $districts;
    }
}
