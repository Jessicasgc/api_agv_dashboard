<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\AGV;
use Validator;
use Illuminate\Support\Str;
class AGVController extends Controller
{
    public function index()
    {
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

    public function show($id)
    {
        $agv = AGV::find($id); 
        $uuid_agv = $agv->uuid_agv;

        if(!is_null($agv)){
            return response([
                'success' => true,
                'message' => 'Retrieve Data $uuid_agv Success',
                'data' => $agv
            ], 200);
        }

        return response([
            'success' => false,
            'message' => '$uuid_agv Not Found',
            'data' => null
        ], 404);
    
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 
        $agv_code = 'AGV-' . $uniqueCode;
     

        $storeData['agv_code'] = $agv_code;
        
        $validate = Validator::make($storeData, [
            'agv_name' => 'required|unique:agv',
            'agv_status' => 'required',
            'is_charging' => 'required',
        ]);
        $agv_name = $request->input('agv_name');

        if($validate->fails())
            return response(['message' => $validate->errors()], 400);
            
        $agv = AGV::create($storeData); 
        //dd($agv);
        if ($agv) {
            return response([
                'success' => true,
                'message' => "Create Data $agv_name Task Success",
                'data' => $agv
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $agv_name failed to create",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Data $agv_name failed to create",
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $agv = AGV::find($id);
        $uuid_agv = $agv->uuid_agv;
        
        if(is_null($agv)){
            return response()->json([
                'status' => 'failed',
                'message' => 'Data not found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'status' => 'required',
            'is_charging' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);

            $agv->status = $updateData['status'];
            $agv->is_charging = $updateData['is_charging'];

            if($agv->save()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data $uuid_agv updated successfully',
                    'data' => $agv
                ], 200);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_agv failed to update',
                    'data' => null
                ], 400);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_agv failed to update',
                    'data' => null
                ], 500);
            }
        }
    }

    public function destroy($id)
    {
        $agv = AGV::find($id);
        $uuid_agv = $agv->uuid_agv;

        if(is_null($agv)){
            return response([
                'message' => '$uuid_agv Not Found',
                'data' => null
            ], 404);
        }

        if($agv->delete()){
            return response([
                'message' => 'Delete $uuid_agv Success',
                'data' => $agv
            ], 200);
        }

        return response([
            'message' => 'Delete $uuid_agv Failed',
            'data' => $agv
        ], 400);
    }
}
