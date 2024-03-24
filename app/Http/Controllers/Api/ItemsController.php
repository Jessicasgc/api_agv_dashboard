<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Item;
use Validator;

class ItemsController extends Controller
{
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

    public function show($id)
    {
        $item = Item::find($id); //mencari data kamar berdasarkan id

        if(!is_null($item)){
            return response([
                'success' => true,
                'message' => 'Retrieve Data Item Success',
                'data' => $item
            ], 200);
        }

        return response([
            'success' => false,
            'message' => 'Item Not Found',
            'data' => null
        ], 404);
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $uuid_item = IdGenerator::generate(['table' => 'items', 'field' => 'uuid_item', 'length' => 10, 'prefix' => date('Item-')]);
        $storeData['uuid_item'] = $uuid_item;
        
        $validate = Validator::make($storeData, [
            'uuid_item' => 'required'|'regex:/^Item-\d{10}$/'|'unique:items',
            'id_type'=>'required',
            'id_station'=>'required',
            'name'=>'required',
        ]);
       

        if($validate->fails())
            return response(['message' => $validate->errors()], 400);
            
        $item = Item::create($storeData); 
        dd($item);
        if ($item) {
            return response([
                'success' => true,
                'message' => 'Create Data {$uuid_item} Success',
                'data' => $item
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data '.$uuid_item.' failed to create',
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_item failed to create',
                'data' => null
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        $uuid_item = $item->uuid_item;
        
        if(is_null($item)){
            return response()->json([
                'status' => 'failed',
                'message' => 'Data {$uuid_item} not found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        $validate = Validator::make($updateData, [
            'id_type'=>'required',
            'id_station'=>'required',
            'name'=>'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);

            $item->id_type = $updateData['id_type'];
            $item->id_station = $updateData['id_station'];
            $item->name = $updateData['name'];

            if($item->save()){
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data $uuid_item updated successfully',
                    'data' => $item
                ], 200);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_item failed to update',
                    'data' => null
                ], 400);
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data $uuid_item failed to update',
                    'data' => null
                ], 500);
            }
        }
    }

    public function destroy($id)
    {
        $item = Item::find($id);
        $uuid_item = $item->uuid_item;

        if(is_null($item)){
            return response([
                'message' => '$uuid_item Not Found',
                'data' => null
            ], 404);
        }

        if($item->delete()){
            return response([
                'message' => 'Delete $uuid_item Success',
                'data' => $item
            ], 200);
        }

        return response([
            'message' => 'Delete $uuid_item Failed',
            'data' => $item
        ], 400);
    }
}
