<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Station;
use App\Models\ItemType;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Str;


class StationController extends Controller
{  
    public function __construct()
    {
        $this->middleware('log.action')->only(['store', 'updateById', 'destroyById']);
    }
    public function index()
    {
        $stations = Station::all();

        if(count($stations) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'Data found',
                'data' => $stations
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Data is Empty',
            'data' => null
        ], 404);
    }

    public function show($station_name)
    {
        
        $station = Station::where('station_name', $station_name)->first();
        if (is_null($station)) {
            return response([
                'success' => falseid_type,
                'message' => "$station_name Not Found",
                'data' => null
            ], 404);
        }
       
        if(!is_null($station)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $station_name Success",
                'data' => $station
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$station_name Not Found",
            'data' => null
        ], 404);
    
    }
    

    public function store(Request $request)
    {
        $storeData = $request->all();
        $random_number = mt_rand(10, 99); 
        $id_type = $request->input('id_type');
    
        \Log::info('Received id_type: ' . $id_type);

        $itemType = ItemType::find($id_type);

        \Log::info('Retrieved ItemType: ' . json_encode($itemType));

        if (!$itemType) {
            return response()->json([
                'success' => false,
                'message' => 'Item type not found',
                'data' => null
            ], 404);
        }
        
        $itemType = ItemType::find($id_type);
        $stationCount = Station::where('id_type', $id_type)->count();
        $station_number = $stationCount + 1;
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 

        $station_name = 'Station-' . $itemType->type_name . '-' . $station_number . '-' . $uniqueCode;
        $storeData['station_name'] = $station_name;
        $storeData['x_agv1'] = $storeData['x'] - 1;
        $storeData['y_agv1'] = $storeData['y'];
        $storeData['x_agv2'] = $storeData['x'] + 1;
        $storeData['y_agv2'] = $storeData['y'];
        $storeData['stock'] = 0;
        
        $validate = Validator::make($storeData, [
            'id_type' => 'required',
            'x' => 'required',
            'y' => 'required',
            'x_agv1' => 'required',
            'y_agv1' => 'required',
            'x_agv2' => 'required',
            'y_agv2' => 'required',
            'max_capacity' => 'required'
        ]);

        if ($validate->fails()) {
            return response(['message' => $validate->errors()], 400);
        }
        
        $station = Station::create($storeData); 

        if ($station) {
            return response([
                'status' => 'success',
                'message' => "Create Data $station_name Success",
                'data' => $station
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $station_name failed to create",
                'data' => null
            ], 400);
        }
    }

    public function updateById(Request $request, $id){
        $station = Station::find($id);

        if (is_null($station)) {
            return response()->json([
                'status' => 'failed',
                'message' => "Station Data with ID $id not found",
                'data' => null
            ], 404);
        }
        
        $updateData = $request->all();
        $id_type = $updateData['id_type'];
        $itemType = ItemType::find($id_type);
        $stationCount = Station::where('id_type', $id_type)->count();
        $station_number = $stationCount + 1;

        $parts = explode('-', $station->station_name);
        if (count($parts) >= 4) {
            $uniqueCode = $parts[3];
        } else {
            // Generate a new unique code
            $uuid = Str::uuid();
            $uniqueCode = substr($uuid, 0, 8);
        } 

        $new_station_name = 'Station-' . $itemType->type_name . '-' . $station_number . '-' . $uniqueCode;
        $station->station_name = $new_station_name;
    
        // Update other fields if needed
        $station->fill($updateData);
        $validate = Validator::make($updateData, [
            'id_type' => 'required',
            'x' => 'required',
            'y' => 'required',
            'stock' => 'required',
            'max_capacity' => 'required'
        ]);
        
        if ($validate->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }
        
        $station->id_type = $updateData['id_type'];
        $station->x = $updateData['x'];
        $station->y = $updateData['y'];
        $station->stock = $updateData['stock'];
        $station->max_capacity = $updateData['max_capacity'];
        $station['x_agv1'] = $updateData['x'] - 1;
        $station['y_agv1'] = $updateData['y'];
        $station['x_agv2'] = $updateData['x'] + 1;
        $station['y_agv2'] = $updateData['y'];
        
        if ($station->save()) {
            return response()->json([
                'status' => 'success',
                'message' => "Station Data with ID $id updated successfully",
                'data' => $station
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Station Data with ID $id failed to update",
                'data' => null
            ], 500);
        }
    }

    public function destroyById($id)
    {
        $station = Station::find($id);

        if(is_null($station)){
            return response([
                'message' => "Station Data with ID $id Not Found",
                'data' => null
            ], 404);
        }

        if($station->delete()){
            return response([
                'message' => "Delete Station Data with ID $id Successfully",
                'data' => $station
            ], 200);
        }

        return response([
            'message' => "Delete Station Data with ID $id Failed",
            'data' => $station
        ], 400);
    }

}
