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
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id')->default(0)->nullable(false);
            $table->string('user_type', 40);
            $table->decimal('amount', 28, 8)->default(0);
            $table->unsignedInteger('currency_id')->default(0)->nullable(false);
            $table->string('voucher_code', 40);
            $table->tinyInteger('is_used')->default(0);
            $table->integer('redeemer_id')->default(0);

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
        Schema::dropIfExists('vouchers');
    }
};
