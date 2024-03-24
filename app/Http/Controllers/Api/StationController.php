<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Station;
use App\Models\ItemType;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;


class StationController extends Controller
{
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
                'success' => false,
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
        
        $itemType = ItemType::find($id_type);
        
        if (!$itemType) {
            return response()->json([
                'success' => false,
                'message' => 'Item type not found',
                'data' => null
            ], 404);
        }
        
        $stationCount = Station::where('id_type', $id_type)->count();
        $station_number = $stationCount + 1;
        $station_name = 'Station-' . $itemType->name . '-' . $station_number;
        $storeData['station_code'] = $stationCount;
        
        $storeData['station_name'] = $station_name;
        
        $validate = Validator::make($storeData, [
            'id_type' => 'required',
            'x' => 'required',
            'y' => 'required',
            'stock' => 'required',
            'max_capacity' => 'required'
        ]);
    
        if ($validate->fails()) {
            return response(['message' => $validate->errors()], 400);
        }
        
        $station = Station::create($storeData); 
    
        if ($station) {
            return response([
                'success' => true,
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

    public function updateByName(Request $request, $station_name)
    {
        $station = Station::where('station_name', $station_name)->first();
        
        if (!$station) {
            return response()->json([
                'status' => 'failed',
                'message' => "Station $station_name not found",
                'data' => null
            ], 404);
        }
        
        $updateData = $request->all();
        
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
        
        if ($station->save()) {
            return response()->json([
                'status' => 'success',
                'message' => "Data $station_name updated successfully",
                'data' => $station
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $station_name failed to update",
                'data' => null
            ], 500);
        }
    }

    public function updateById(Request $request, $id){
        $station = Station::find($id);
        var_dump($station);
        if (!$station) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Station not found',
                'data' => null
            ], 404);
        }

        $item_type_name = $station->itemType->name;
        $station_name = IdGenerator::generate(['table' => 'stations', 'field' => 'station_name', 'length' => 10, 'prefix' => 'Station-'. $item_type_name]);
        $storeData['station_name'] = $station_name;
        if(is_null($station)){
            return response()->json([
                'status' => 'failed',
                'message' => 'Station not found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_type' => 'required',
            'x' => 'required',
            'y' => 'required',
            'stock' => 'required',
            'max_capacity' => 'required'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);

            $station->id_type = $updateData['type'];
            $station->x = $updateData['x'];
            $station->y = $updateData['y'];
            $station->stock = $updateData['stock'];
            $station->max_capacity = $updateData['max_capacity'];

            if($station->save()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data $uuid_station updated successfully',
                    'data' => $station
                ], 200);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_station failed to update',
                    'data' => null
                ], 400);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_station failed to update',
                    'data' => null
                ], 500);
            }
        }
    }

    public function destroy($id)
    {
        $station = Station::find($id);
        $uuid_station = $station->uuid_station;

        if(is_null($station)){
            return response([
                'message' => '$uuid_station Not Found',
                'data' => null
            ], 404);
        }

        if($station->delete()){
            return response([
                'message' => 'Delete $uuid_station Success',
                'data' => $station
            ], 200);
        }

        return response([
            'message' => 'Delete $uuid_station Failed',
            'data' => $station
        ], 400);
    }
}
