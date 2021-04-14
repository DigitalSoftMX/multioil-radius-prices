<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIslandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('islands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('station_id');
            $table->integer('number');
            $table->integer('bombs');
            $table->timestamps();

            $table->foreign('station_id')->references('id')->on('stations')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('islands');
    }
}
