<?php

use App\Events\TestPusher;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
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

Route::get('/test-pusher', function() {
    event(new TestPusher());
    return "Event triggered";
});

Route::group(["middleware" => "apikey"], function() {
    Route::group(["prefix" => "general"], function() {
        Route::get("/provinces", [RegionController::class, 'getProvinces']);
        Route::get("/regencies/{provinceId}", [RegionController::class, 'getRegencies']);
        Route::get("/districts/{regencyId}", [RegionController::class, 'getDistricts']);

        Route::get('/animals', [AnimalController::class, 'getAllAnimals']);
        Route::get('/animals/categories', [AnimalController::class, 'getAllCategories']);
        Route::post('/animals', [AnimalController::class, 'store']);

        Route::get('/organizations', [OrganizationController::class, 'getAllOrganizations']);
        Route::post('/organizations', [OrganizationController::class, 'store']);

        Route::get('/sites', [SiteController::class, 'getAllSites']);
        Route::post('/sites', [SiteController::class, 'store']);

        Route::get('/news', [NewsController::class, 'getAllNews']);
        Route::post('/news', [NewsController::class, 'store']);

        Route::get('/keywords', [UserController::class, 'getKeywords']);

        Route::get('/check-url', [NewsController::class, 'checkLink']);

        Route::group(["prefix" => "trending"], function () {
            Route::get('/animals', [AnimalController::class, 'getNumbersOfCases']);
            Route::get('/rising', [AnimalController::class, 'getRisingCases']);
            Route::get('/region', [RegionController::class, 'getTrendingProvinces']);
        });
    });

    Route::group(["prefix" => "auth"], function() {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        Route::group(['middleware' => 'auth:sanctum'], function() {
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });
});
