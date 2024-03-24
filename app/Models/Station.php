<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Station extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'station_name',
        'id_type',
        'x',
        'y',
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
        return $this->belongsTo(ItemType::class, 'id_type');
    }
}
