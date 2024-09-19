<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AGVTracking extends Model
{
    use HasFactory;
    protected $table = 'agv_trackings';
    protected $fillable = [
        'id',
        'id_agv_choosen',
        'agv_code_choosen',
        'agv_name_choosen',
        'agv_status_choosen',
        'position_choosen',
        'power_choosen',
        'speed_choosen',
        'id_task',
        'start_station_name',
        'destination_station_name',
        'task_code',
        'task_name',
        'task_status',
        'item_name',
        'start_time',
        'end_time',
        'id_agv_1',
        'agv_code_1',
        'agv_name_1',
        'agv_status_1',
        'position_1',
        'power_1',
        'speed_1',
        'id_agv_2',
        'agv_code_2',
        'agv_name_2',
        'agv_status_2',
        'position_2',
        'power_2',
        'speed_2',
    ];

    protected $spatialFields = [
        'position_choosen',
        'position_1',
        'position_2'
    ];

    public function getCreatedAtAttribute(){
        if(!is_null($this->attributes['created_at'])){
            return Carbon::parse($this->attributes['created_at'])->format('Y-m-d H:i:s');
        }
    }
    public function getUpdateAtAttribute(){
        if(!is_null($this->attributes['update_at'])){
            return Carbon::parse($this->attributes['update_at'])->format('Y-m-d H:i:s');
        }
    }
}
