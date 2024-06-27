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
        Schema::create('agv', function (Blueprint $table) {
            $table->id();
            $table->string('agv_code');
            $table->string('agv_name');
            $table->string('agv_status');
            // $table->boolean('is_charging');
            $table->point('position');
            $table->integer('power');
            $table->float('speed');
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
        Schema::dropIfExists('agv');
    }
};