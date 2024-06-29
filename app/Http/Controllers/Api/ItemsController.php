<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Item;
use Validator;
use Illuminate\Support\Str;
use App\Models\ActionLog;
class ItemsController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.action:item')->only(['store', 'updateById', 'destroyById']);
    }
    public function index()
    {
        $items = Item::all();

        if(count($items) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'Data found',
                'data' => $items
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
        $item = Item::find($id); //mencari data kamar berdasarkan id
        $item_name = $item->item_name;
        if(!is_null($item)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $item_name Success",
                'data' => $item
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$item_code Data with name $item_name Not Found",
            'data' => null
        ], 404);
    }

    public function showByName($item_name)
    {
        $item = Item::where('item_name', $item_name)->get(); //mencari data kamar berdasarkan id
        
        if(!is_null($item)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $item_name Success",
                'data' => $item
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$item_name Data Not Found",
            'data' => null
        ], 404);
    }

    public function showByCode($item_code)
    {
        $item = Item::where('item_code', $item_code)->get(); //mencari data kamar berdasarkan id
        
        if(!is_null($item)){
            return response([
                'success' => true,
                'message' => "Retrieve Data $item_code Success",
                'data' => $item
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "$item_code Data Not Found",
            'data' => null
        ], 404);
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 
       
        $validate = Validator::make($storeData, [
            'id_type'=>'required',
            'item_name'=>'required',
        ]);
       
        $item_code =  $storeData['item_name'] . '-' . $uniqueCode;

        $storeData['item_code'] = $item_code;
        
        if($validate->fails())
            return response(['message' => $validate->errors()], 400);
            
        $item = Item::create($storeData); 
        
        if ($item) {
            return response([
                'success' => true,
                'message' => "Create Data $item_code Success",
                'data' => $item
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $item_code failed to create",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Data $item_code failed to create",
                'data' => null
            ], 500);
        }
    }

    public function updateById(Request $request, $id)
    {
        $item = Item::find($id);

        if(is_null($item)){
            return response()->json([
                'status' => 'failed',
                'message' => "Item with ID $id not found",
                'data' => null
            ], 404);
        }

        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_type' => 'required',
            'item_name' => 'required',
        ]);

        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

        $item->id_type = $updateData['id_type'];
        $item->item_name = $updateData['item_name'];

        if($item->save()){
            return response()->json([
                'status' => 'success',
                'message' => "Item with ID $id updated successfully",
                'data' => $item
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Failed to update item with ID $id",
                'data' => null
            ], 500);
        }
    }

public function updateByCode(Request $request, $item_code)
{
    $item = Item::where('item_code', $item_code)->first();

    if(is_null($item)){
        return response()->json([
            'status' => 'failed',
            'message' => "Item with code $item_code not found",
            'data' => null
        ], 404);
    }

    $updateData = $request->all();
    $validate = Validator::make($updateData, [
        'id_type' => 'required',
        'item_name' => 'required',
    ]);

    if($validate->fails()){
        return response()->json([
            'status' => 'failed',
            'message' => 'Validation Error',
            'data' => $validate->errors()
        ], 400);
    }

    $item->id_type = $updateData['id_type'];
    $item->item_name = $updateData['item_name'];

    if($item->save()){
        return response()->json([
            'status' => 'success',
            'message' => "Item with code $item_code updated successfully",
            'data' => $item
        ], 200);
    } else {
        return response()->json([
            'status' => 'failed',
            'message' => "Failed to update item with code $item_code",
            'data' => null
        ], 500);
    }
}

    public function destroyById($id)
    {
        $item = Item::find($id);

        if(is_null($item)){
            return response([
                'message' => "Item data with ID $id Not Found",
                'data' => null
            ], 404);
        }

        if($item->delete()){
            return response([
                'message' => "Delete Item data with ID $id Success",
                'data' => $item
            ], 200);
        }

        return response([
            'message' => "Delete Item data with ID $id Failed",
            'data' => $item
        ], 400);
    }

    public function destroyByCode($item_code)
    {
        $item = Item::where('item_code', $item_code)->first();

        if(is_null($item)){
            return response([
                'message' => "$item_code Not Found",
                'data' => null
            ], 404);
        }

        if($item->delete()){
            return response([
                'message' => "Delete $item_code Success",
                'data' => $item
            ], 200);
        }

        return response([
            'message' => "Delete $item_code Failed",
            'data' => $item
        ], 400);
    }
}
