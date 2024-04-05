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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id')->default(0)->nullable(false);
            $table->unsignedInteger('currency_id')->default(0)->nullable(false);
            $table->string('user_type', 40);
            $table->string('invoice_num', 40);
            $table->string('invoice_to', 40);
            $table->string('email', 40);
            $table->string('address');
            $table->decimal('charge', 28, 8);
            $table->decimal('total_amount', 28, 8);
            $table->decimal('get_amount', 28, 8);
            $table->tinyInteger('pay_status');
            $table->tinyInteger('status')->comment('1 => Published, 0 => Not published , 2 => Cancel');

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
        Schema::dropIfExists('invoices');
    }
};
