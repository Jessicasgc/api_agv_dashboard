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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_station_input')->nullable()->constrained('stations')->cascadeOnUpdate()->cascadeOnDelete()->default(null);
            $table->foreignId('id_station_output')->nullable()->constrained('stations')->cascadeOnUpdate()->cascadeOnDelete()->default(null);
            $table->string('task_code');
            $table->string('task_name');
            $table->foreignId('id_agv')->constrained('agv');
            $table->foreignId('id_item')->constrained('items');
            $table->string('task_status');
            $table->time('start_time');
            $table->time('end_time');
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
        Schema::dropIfExists('tasks');
    }
};
