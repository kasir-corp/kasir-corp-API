<?php

use App\Http\Controllers\AnimalController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(["prefix" => "general", "middleware" => "apikey"], function() {
    Route::get("/provinces", [RegionController::class, 'getProvinces']);
    Route::get("/regencies/{provinceId}", [RegionController::class, 'getRegencies']);
    Route::get("/districts/{regencyId}", [RegionController::class, 'getDistricts']);

    Route::get('/animals', [AnimalController::class, 'getAllAnimals']);
    Route::post('/animals', [AnimalController::class, 'store']);

    Route::get('/organizations', [OrganizationController::class, 'getAllOrganizations']);
    Route::post('/organizations', [OrganizationController::class, 'store']);

    Route::get('/sites', [SiteController::class, 'getAllSites']);
    Route::post('/sites', [SiteController::class, 'store']);
});
