<?php

namespace App\Http\Traits;

use App\Models\AGV;

trait WebsocketTrait
{
    public function store()
    {

        return AGV::all();
    }


}