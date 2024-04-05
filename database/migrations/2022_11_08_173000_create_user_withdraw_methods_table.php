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
        Schema::create('user_withdraw_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unsignedInteger('user_id')->nullable(false)->default(0);
            $table->string('user_type', 40);
            $table->unsignedInteger('method_id')->nullable(false)->default(0);
            $table->unsignedInteger('currency_id')->nullable(false)->default(0);
            $table->text('user_data');
            $table->tinyInteger('status')->default(1);

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
        Schema::dropIfExists('user_withdraw_methods');
    }
};
