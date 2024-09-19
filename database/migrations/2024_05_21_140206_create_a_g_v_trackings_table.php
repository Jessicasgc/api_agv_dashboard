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
        Schema::create('agv_trackings', function (Blueprint $table) {
            $table->id();
            $table->integer('id_agv_choosen')->nullable();
            $table->string('agv_name_choosen')->nullable();
            $table->string('agv_code_choosen')->nullable();
            $table->string('agv_status_choosen')->nullable();
            $table->point('position_choosen')->nullable();
            $table->integer('power_choosen')->nullable();
            $table->float('speed_choosen')->nullable();
            $table->integer('id_task')->nullable();
            $table->string('start_station_name')->nullable();
            $table->string('destination_station_name')->nullable();
            $table->string('task_code')->nullable();
            $table->string('task_name')->nullable();
            $table->string('task_status')->nullable();
            $table->string('item_name')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('id_agv_1')->nullable();
            $table->string('agv_code_1')->nullable();
            $table->string('agv_name_1')->nullable();
            $table->string('agv_status_1')->nullable();
            $table->point('position_1')->nullable();
            $table->integer('power_1')->nullable();
            $table->float('speed_1')->nullable();
            $table->integer('id_agv_2')->nullable();
            $table->string('agv_code_2')->nullable();
            $table->string('agv_name_2')->nullable();
            $table->string('agv_status_2')->nullable();
            $table->point('position_2')->nullable();
            $table->integer('power_2')->nullable();
            $table->float('speed_2')->nullable();
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
        Schema::dropIfExists('agv_trackings');
    }
};
