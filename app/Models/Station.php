<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Events\WebsocketEvent;

class Station extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'station_name',
        'id_type',
        'x',
        'y',
        'x_agv1',
        'y_agv1',
        'x_agv2',
        'y_agv2',
        'stock',
        'max_capacity',
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
    public function itemType()
    {
        return $this->belongsTo(ItemType::class, 'id');
    }
    protected static function booted()
    {
        static::updated(function ($station) {
            if ($station->isDirty(['x', 'y'])) {
                event(new WebsocketEvent($station));
            }
        });
    }
}
