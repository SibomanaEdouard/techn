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
        Schema::create('qr_rcodes', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id')->default(0)->nullable(false);
            $table->string('user_type', 40)->comment('1=> USER, 2=> AGENT, 3=>MERCHANT');
            $table->string('unique_code');

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
        Schema::dropIfExists('q_rcodes');
    }
};
