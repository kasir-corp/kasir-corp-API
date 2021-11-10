<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePivotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_organization', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('organization_id');
            $table->timestamps();
        });

        Schema::create('news_regency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('regency_id');
            $table->timestamps();
        });

        Schema::create('animal_news', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('news_id');
            $table->integer('amount')->nullable();
            $table->timestamps();
        });

        Schema::table('news_organization', function (Blueprint $table) {
            $table->foreign('news_id')->references('id')->on('news');
            $table->foreign('organization_id')->references('id')->on('organizations');
        });

        Schema::table('news_regency', function (Blueprint $table) {
            $table->foreign('news_id')->references('id')->on('news');
            $table->foreign('regency_id')->references('id')->on('regencies');
        });

        Schema::table('animal_news', function (Blueprint $table) {
            $table->foreign('animal_id')->references('id')->on('animals');
            $table->foreign('news_id')->references('id')->on('news');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_organization');
        Schema::dropIfExists('news_regency');
        Schema::dropIfExists('animal_news');
    }
}
