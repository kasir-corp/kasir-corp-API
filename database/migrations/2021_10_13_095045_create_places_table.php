<?php

use App\Imports\RegionImport;
use App\Models\Province;
use App\Models\Regency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class CreatePlacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('longitude');
            $table->double('latitude');
            $table->timestamps();
        });

        Schema::create('regencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('province_id');
            $table->timestamps();
        });

        Schema::table('regencies', function (Blueprint $table) {
            $table->foreign('province_id')->references('id')->on('provinces');
        });

        Artisan::call('db:seed', [
            '--class' => 'RegionSeeder'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('regencies');
        Schema::dropIfExists('provinces');
    }
}
