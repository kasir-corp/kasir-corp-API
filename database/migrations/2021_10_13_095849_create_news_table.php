<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('url');
            $table->date('date')->nullable();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('district_id')->nullable();
            $table->unsignedBigInteger('regency_id')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->boolean('isTrained');
            $table->enum('label', ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others']);
            $table->timestamps();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreign('organization_id')->references('id')->on('organizations');
            $table->foreign('site_id')->references('id')->on('sites');
            $table->foreign('district_id')->references('id')->on('districts');
            $table->foreign('regency_id')->references('id')->on('regencies');
            $table->foreign('province_id')->references('id')->on('provinces');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news');
    }
}
