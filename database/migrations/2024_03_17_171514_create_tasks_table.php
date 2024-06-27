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
            $table->foreignId('id_start_station')->nullable()->constrained('stations')->default(null);
            $table->foreignId('id_destination_station');
            $table->string('task_code');
            $table->string('task_name');
            $table->foreignId('id_agv')->nullable()->constrained('agv');
            $table->foreignId('id_item')->constrained('items')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('task_status');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
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
