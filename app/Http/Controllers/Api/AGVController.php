<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AGV;
use Validator;
use Illuminate\Support\Str;
use App\Services\WebsocketClient;
use App\Events\WebsocketEvent;
use Illuminate\Support\Facades\DB;
use App\Jobs\ReceiveAgvData;
use Illuminate\Support\Facades\Queue;

class AGVController extends Controller
{
    public function index()
    {
        // $this->startWebSocketClient();
        $agvs = AGV::all();
      
        if(count($agvs) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'Data found',
                'data' => $agvs
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Data is Empty',
            'data' => null
        ], 404);
    }

    public function showById($id)
    {
        $agv = AGV::find($id); 

        if(!is_null($agv)){
            return response([
                'success' => true,
                'message' => "Retrieve AGV Data with ID $id Success",
                'data' => $agv
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "AGV Data with ID $id Not Found",
            'data' => null
        ], 404);
    
    }

    public function showByName($agv_name)
    {
        
        $agv = AGV::where('agv_name', $agv_name)->first();
        if (is_null($agv)) {
            return response([
                'success' => false,
                'message' => "$agv_name Not Found",
                'data' => null
            ], 404);
        }
       
        if(!is_null($agv)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $agv_name Success",
                'data' => $agv
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$agv_name Not Found",
            'data' => null
        ], 404);
    
    }

    
}
