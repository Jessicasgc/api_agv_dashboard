<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_agv',
        'id_station_input',
        'id_station_output',
        'id_item',
        'task_code',
        'task_name',
        'task_status',
        'start_time',
        'end_time',
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

    public function agv()
    {
        return $this->belongsTo(AGV::class, 'id_agv');
    }

    public function station()
    {
        return $this->belongsTo(Station::class, 'id_station');
    }
    
    public function item()
    {
        return $this->belongsTo(Item::class, 'id_item');
    }


}
