<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('stripe_id');
            $table->double('balance');
            $table->string('balance_transaction');
            $table->string('currency');
            $table->string('metadata');
            $table->string('payment_intent');
            $table->integer('refunded');
            $table->string('stripe_status');
            $table->integer('amount_captured');
            $table->integer('amount_refunded');
            $table->string('application')->nullable();
            $table->string('application_fee')->nullable();
            $table->string('application_fee_amount')->nullable();
            $table->string('calculated_statement_descriptor');
            $table->timestamp('created');
            $table->string('failure_message')->nullable();
            $table->integer('livemode');
            $table->string('order')->nullable();
            $table->integer('paid');
            $table->string('payment_method');
            $table->string('receipt_number');
            $table->string('receipt_url');
            $table->unsignedBigInteger('status');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('status')->references('id')->on('status')
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
        Schema::dropIfExists('deposits');
    }
}
