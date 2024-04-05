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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->string('currency_code')->index();
            $table->string('currency_symbol', 40);
            $table->string('currency_fullname');
            $table->tinyInteger('currency_type')->comment('1=>Fiat, 2=>Crypto')->unsigned();
            $table->decimal('rate', 28, 8)->default(0);
            $table->tinyInteger('is_default')->default(0)->unsigned();
            $table->tinyInteger('status')->default(1)->unsigned()->comment('0=>Inactive, 1=>Active');

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
        Schema::dropIfExists('currencies');
    }
};
