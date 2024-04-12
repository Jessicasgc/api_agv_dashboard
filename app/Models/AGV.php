<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AGV extends Model
{
    use HasFactory;
    protected $table = 'agv';
    protected $fillable = [
        'id',
        'agv_name',
        'agv_code',
        'agv_status',
        'is_charging'
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
    public function tasks()
    {
        return $this->hasMany(Task::class, 'id_agv');
    }
}
