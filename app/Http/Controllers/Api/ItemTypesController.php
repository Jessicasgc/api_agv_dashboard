<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ItemType;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class ItemTypesController extends Controller
{
    public function index()
    {
        $itemtypes = ItemType::all();

        if(count($itemtypes) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'Item Type Data found',
                'data' => $itemtypes
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Item Type is Empty',
            'data' => null
        ], 404);
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $type_code = IdGenerator::generate(['table' => 'item_types', 'field' => 'type_code', 'length' => 10, 'prefix' => 'Type-']);
        $storeData['type_code'] = $type_code;
        $validate = Validator::make($storeData, [
            'type_name' => 'required'
        ]);
       

        if($validate->fails())
            return response(['message' => $validate->errors()], 400);
            
        $item_type = ItemType::create($storeData); 
        //dd($item_type);
        if ($item_type) {
            return response([
                'success' => true,
                'message' => "Create Data $type_code Task Success",
                'data' => $item_type
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data  $type_code' failed to create",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Data $type_code failed to create",
                'data' => null
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $item_type = ItemType::find($id);
        if(is_null($item_type)){
            return response()->json([
                'status' => 'failed',
                'message' => 'Data not found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'name' => 'required'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);

            $item_type->name = $updateData['type_name'];

            if($item_type->save()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data  updated successfully',
                    'data' => $item_type
                ], 200);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data failed to update',
                    'data' => null
                ], 400);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data failed to update',
                    'data' => null
                ], 500);
            }
        }
    }
    public function destroy($id)
    {
        $item_type = ItemType::find($id);
        $uuid_type = $item_type->uuid_type;

        if(is_null($item_type)){
            return response([
                'message' => "$type_code Not Found",
                'data' => null
            ], 404);
        }

        if($item_type->delete()){
            return response([
                'message' => "Delete $type_code Success",
                'data' => $item_type
            ], 200);
        }

        return response([
            'message' => "Delete $type_code Failed",
            'data' => $item_type
        ], 400);
    }
    // public function destroy($id)
    // {
    //     $item_type = ItemType::find($id);
    //     $uuid_type = $item_type->uuid_type;

    //     if(is_null($item_type)){
    //         return response([
    //             'message' => '$uuid_type Not Found',
    //             'data' => null
    //         ], 404);
    //     }

    //     if($item_type->delete()){
    //         return response([
    //             'message' => 'Delete $uuid_type Success',
    //             'data' => $item_type
    //         ], 200);
    //     }

    //     return response([
    //         'message' => 'Delete $uuid_type Failed',
    //         'data' => $item_type
    //     ], 400);
    // }
}
