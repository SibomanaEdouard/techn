<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_money', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('currency_id')->nullable(false)->default(0);
            $table->unsignedInteger('wallet_id')->nullable(false)->default(0);
            $table->decimal('charge', 28, 8)->default(0);
            $table->decimal('request_amount', 28, 8)->default(0);
            $table->unsignedInteger('sender_id')->default(0);
            $table->unsignedInteger('receiver_id')->default(0);
            $table->text('note');
            $table->tinyInteger('status')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_money');
    }
};
