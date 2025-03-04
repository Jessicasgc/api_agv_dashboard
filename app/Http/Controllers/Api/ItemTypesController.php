<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ItemType;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Str;

class ItemTypesController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.action')->only(['store', 'updateById', 'destroyById']);
    }
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

    public function show($type_name)
    {
        
        $item_type = ItemType::where('type_name', $type_name)->first();
        if (is_null($item_type)) {
            return response([
                'success' => false,
                'message' => "$type_name Not Found",
                'data' => null
            ], 404);
        }
       
        if(!is_null($item_type)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $type_name Success",
                'data' => $item_type
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$type_name Not Found",
            'data' => null
        ], 404);
    
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 
        $type_code = 'Type-' . $uniqueCode;

        $storeData['type_code'] = $type_code;
        $validate = Validator::make($storeData, [
            'type_name' => 'required|unique:item_types'
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

    public function updateById(Request $request, $id)
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
            'type_name' => 'required'
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $item_type->type_name = $updateData['type_name'];

        if($item_type->save()){
            return response()->json([
                'status' => 'success',
                'message' => "Type Data with ID $id updated successfully",
                'data' => $item_type
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Type Data $id failed to update",
                'data' => null
            ], 500);
        }
    }

    public function destroyById($id)
    {
        $item_type = ItemType::find($id);

        if(is_null($item_type)){
            return response([
                'status' => 'failed',
                'message' => "Item Type with ID $id Not Found",
                'data' => null
            ], 404);
        }

        if($item_type->delete()){
            return response([
                'status' => 'success',
                'message' => "Delete Item Type with ID $id Success",
                'data' => $item_type
            ], 200);
        }

        return response([
            'status' => 'failed',
            'message' => "Delete Item Type with ID $id Failed",
            'data' => $item_type
        ], 400);
    }
}
