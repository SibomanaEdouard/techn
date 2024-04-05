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
        Schema::create('transaction_charges', function (Blueprint $table) {
            $table->id();

            $table->string('slug', 40)->index();
            $table->decimal('fixed_charge', 28, 8)->default(0);
            $table->decimal('percent_charge', 5, 2)->default(0);
            $table->decimal('min_limit', 28, 8)->default(0);
            $table->decimal('max_limit', 28, 8)->default(0);
            $table->decimal('agent_commission_fixed', 28, 8)->default(0);
            $table->decimal('agent_commission_percent', 5, 2)->default(0);
            $table->decimal('merchant_fixed_charge', 28, 8)->default(0);
            $table->decimal('merchant_percent_charge', 5, 2)->default(0);
            $table->decimal('monthly_limit', 28, 8)->default(0);
            $table->decimal('daily_limit', 28, 8)->default(0);
            $table->integer('voucher_limit')->default(0);
            $table->decimal('cap', 28, 8)->default(0);

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
        Schema::dropIfExists('transaction_charges');
    }
};
