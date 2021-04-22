<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('station_id');
            $table->string('sale');
            $table->string('gasoline');
            $table->double('payment');
            $table->double('liters');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('dispatcher_id');
            $table->unsignedBigInteger('time_id');
            $table->unsignedBigInteger('schedule_id');
            $table->integer('no_island');
            $table->integer('no_bomb');
            $table->unsignedBigInteger('sponsor_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('station_id')->references('id')->on('stations')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('client_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('dispatcher_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('time_id')->references('id')->on('register_times')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('schedule_id')->references('id')->on('schedules')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('sponsor_id')->references('id')->on('users')
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
        Schema::dropIfExists('sales');
    }
}
