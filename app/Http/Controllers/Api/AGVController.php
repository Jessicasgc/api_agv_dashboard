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
    public function updateByName(Request $request, $agv_name)
    {
        $agv = AGV::where('agv_name', $agv_name)->first();

        if(is_null($agv)){
            return response()->json([
                'status' => 'failed',
                'message' => "AGV Data $agv_name not found",
                'data' => null
            ], 404);
        }

        $updateData = $request->all();
    
        $validate = Validator::make($updateData, [
            'agv_name' => 'required|unique:agv,agv_name,'.$agv->id,
            'agv_status' => 'required',
            'is_charging' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $agv->agv_name = $updateData['agv_name'];
        $agv->agv_status = $updateData['agv_status'];
        $agv->is_charging = $updateData['is_charging'];

        if($agv->save()){
            return response()->json([
                'status' => 'success',
                'message' => "AGV Data with name $agv_name updated successfully",
                'data' => $agv
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "AGV Data with name $agv_name failed to update",
                'data' => null
            ], 500);
        }
    }

    public function updateById(Request $request, $id)
    {
        $agv = AGV::find($id);
        if(is_null($agv)){
            return response()->json([
                'status' => 'failed',
                'message' => "Data with ID $id not found",
                'data' => null
            ], 404);
        }

        $updateData = $request->all();
        
        $validate = Validator::make($updateData, [
            'agv_name' => 'required|unique:agv,agv_name,'.$id,
            'agv_status' => 'required',
            'is_charging' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $agv->agv_name = $updateData['agv_name'];
        $agv->agv_status = $updateData['agv_status'];
        $agv->is_charging = $updateData['is_charging'];

        if($agv->save()){
            return response()->json([
                'status' => 'success',
                'message' => "AGV Data with ID $id updated successfully",
                'data' => $agv
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "AGV Data with ID $id failed to update",
                'data' => null
            ], 500);
        }
    }

    public function destroyById($id)
    {
        $agv = AGV::find($id);
        $agv_name = $agv->agv_name;
        if(is_null($agv)){
            return response([
                'message' => "$id Not Found",
                'data' => null
            ], 404);
        }

        if($agv->delete()){
            return response([
                'message' => "Delete $agv_name Success",
                'data' => $agv
            ], 200);
        }

        return response([
            'message' => "Delete $agv_name Failed",
            'data' => $agv
        ], 400);
    }

    public function destroyByName($agv_name)
    {
        $agv = AGV::where('agv_name', $agv_name)->first();
        
        if(is_null($agv)){
            return response([
                'message' => "AGV Data with $agv_name Not Found",
                'data' => null
            ], 404);
        }

        if($agv->delete()){
            return response([
                'message' => "Delete $agv_name Success",
                'data' => $agv
            ], 200);
        }

        return response([
            'message' => "Delete $agv_name Failed",
            'data' => $agv
        ], 400);
    }
}
