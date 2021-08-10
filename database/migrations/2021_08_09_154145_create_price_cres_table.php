<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceCresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_cres', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cree_id');
            $table->double('regular')->nullable();
            $table->double('premium')->nullable();
            $table->double('diesel')->nullable();
            $table->timestamps();

            $table->foreign('cree_id')->references('id')->on('crees')
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
        Schema::dropIfExists('price_cres');
    }
}
