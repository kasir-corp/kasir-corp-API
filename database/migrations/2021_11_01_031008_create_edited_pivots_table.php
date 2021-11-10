<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEditedPivotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edited_animal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('news_id');
            $table->integer('amount')->nullable();
            $table->timestamps();
        });

        Schema::create('edited_regency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('regency_id');
            $table->timestamps();
        });

        Schema::create('edited_organization', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id');
            $table->unsignedBigInteger('organization_id');
            $table->timestamps();
        });

        Schema::table('edited_animal', function (Blueprint $table) {
            $table->foreign('animal_id')->references('id')->on('animals');
            $table->foreign('news_id')->references('id')->on('news');
        });


        Schema::table('edited_regency', function (Blueprint $table) {
            $table->foreign('news_id')->references('id')->on('news');
            $table->foreign('regency_id')->references('id')->on('regencies');
        });

        Schema::table('edited_organization', function (Blueprint $table) {
            $table->foreign('news_id')->references('id')->on('news');
            $table->foreign('organization_id')->references('id')->on('organizations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edited_animal');
        Schema::dropIfExists('edited_regency');
        Schema::dropIfExists('edited_organization');
    }
}
