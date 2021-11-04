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
            $table->text('url');
            $table->date('date')->nullable();
            $table->date('news_date')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->boolean('is_trained');
            $table->enum('label', ['penyelundupan', 'penyitaan', 'perburuan', 'perdagangan', 'others']);
            $table->timestamps();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreign('site_id')->references('id')->on('sites');
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
