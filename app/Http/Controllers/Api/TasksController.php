<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Task;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Str;
use App\Models\Item;
use App\Models\Station;


class TasksController extends Controller
{
    public function index()
    {
        $tasks = Task::all();

        if(count($tasks) > 0){
            return response()->json([
                'status' => 'success',
                'message' => 'Get Task successfully',
                'data' => $tasks
            ], 200);
        }
        return response()->json([
            'status' => 'failed',
            'message' => 'Task is Empty',
            'data' => null
        ], 404);
    }

    public function store(Request $request)
    {
        $storeData = $request->all();
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 
        $task_code = 'Task-' . $uniqueCode;

        $storeData['task_code'] = $task_code;
        
        if (!empty($storeData['id_station_input']) && empty($storeData['id_station_output'])) {
            // Generating task name for item entering station
            $item = Item::find($storeData['id_item']);
            $stationInput = Station::find($storeData['id_station_input']);
            $task_name = "Memasukkan {$item->item_name} ke {$stationInput->station_name}";
        } elseif (empty($storeData['id_station_input']) && !empty($storeData['id_station_output'])) {
            // Generating task name for item exiting station
            $item = Item::find($storeData['id_item']);
            $stationOutput = Station::find($storeData['id_station_output']);
            $task_name = "Mengeluarkan {$item->item_name} dari {$stationOutput->station_name}";
        } elseif (!empty($storeData['id_station_input']) && !empty($storeData['id_station_output'])) {
            // Generating task name for item transferring between stations
            $item = Item::find($storeData['id_item']);
            $stationInput = Station::find($storeData['id_station_input']);
            $stationOutput = Station::find($storeData['id_station_output']);
            $task_name = "Memindahkan {$item->item_name} dari {$stationOutput->station_name} ke {$stationInput->station_name}";
        } else {
            // If neither station is provided, task name cannot be generated
            return response()->json([
                'status' => 'failed',
                'message' => 'Please provide either input or output station',
                'data' => null
            ], 400);
        }

        $storeData['task_name'] = $task_name;

        $validate = Validator::make($storeData, [
            'id_agv' => 'required',
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            'task_status' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if($validate->fails())
            return response(['message' => $validate->errors()], 400);
            
        $task = Task::create($storeData); 
        
        if ($task) {
            return response([
                'success' => true,
                'message' => "Create Data $task_code Task Success",
                'data' => $task
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $task_code failed to create",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Data $task_code failed to create",
                'data' => null
            ], 500);
        }
    }


    public function updateById(Request $request, $id)
    {
        $task = Task::find($id);
    
        if(is_null($task)){
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with ID $id not found",
                'data' => null
            ], 404);
        }
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_agv' => 'required',
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            'task_name',
            'task_status' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }
        $task->id_agv = $updateData['id_agv'];
        $task->id_station_input = $updateData['id_station_input'];
        $task->id_station_output = $updateData['id_station_output'];
        $task->id_item = $updateData['id_item'];
        $task->task_status = $updateData['task_status'];
        $task->start_time = $updateData['start_time'];
        $task->end_time = $updateData['end_time'];

        // Generate task name based on the provided stations
        if (!empty($updateData['id_station_input']) && empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationInput = Station::find($updateData['id_station_input']);
            if (!$stationInput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid input station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Memasukkan item {$item->item_name} ke {$stationInput->station_name}";
        } elseif (empty($updateData['id_station_input']) && !empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationOutput = Station::find($updateData['id_station_output']);
            if (!$stationOutput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid output station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Mengeluarkan item {$item->item_name} dari {$stationOutput->station_name}";
        } elseif (!empty($updateData['id_station_input']) && !empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationInput = Station::find($updateData['id_station_input']);
            $stationOutput = Station::find($updateData['id_station_output']);
            if (!$stationInput || !$stationOutput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid input or output station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Memindahkan item {$item->item_name} dari {$stationOutput->station_name} ke {$stationInput->station_name}";
        } else {
            // If neither station is provided, task name cannot be generated
            return response()->json([
                'status' => 'failed',
                'message' => 'Please provide either input or output station',
                'data' => null
            ], 400);
        }
        $task->task_name = $new_task_name;
        
        if($task->save()){
            return response()->json([
                'status' => 'success',
                'message' => "Task Data with ID $id updated successfully",
                'data' => $task
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with ID $id failed to update",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with ID $id failed to update",
                'data' => null
            ], 500);
        }
    }

    public function updateByCode(Request $request, $task_code)
    {
        $task = Task::where('task_code', $task_code)->first();
    
        if(is_null($task)){
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with name $task_name not found",
                'data' => null
            ], 404);
        }
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_agv' => 'required',
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            'task_status' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
        if($validate->fails()){
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }
        $task->id_agv = $updateData['id_agv'];
        $task->id_station_input = $updateData['id_station_input'];
        $task->id_station_output = $updateData['id_station_output'];
        $task->id_item = $updateData['id_item'];
        $task->task_status = $updateData['task_status'];
        $task->start_time = $updateData['start_time'];
        $task->end_time = $updateData['end_time'];

        // Generate task name based on the provided stations
        if (!empty($updateData['id_station_input']) && empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationInput = Station::find($updateData['id_station_input']);
            if (!$stationInput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid input station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Memasukkan item {$item->item_name} ke {$stationInput->station_name}";
        } elseif (empty($updateData['id_station_input']) && !empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationOutput = Station::find($updateData['id_station_output']);
            if (!$stationOutput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid output station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Mengeluarkan item {$item->item_name} dari {$stationOutput->station_name}";
        } elseif (!empty($updateData['id_station_input']) && !empty($updateData['id_station_output'])) {
            $item = Item::find($updateData['id_item']);
            $stationInput = Station::find($updateData['id_station_input']);
            $stationOutput = Station::find($updateData['id_station_output']);
            if (!$stationInput || !$stationOutput) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Invalid input or output station ID provided',
                    'data' => null
                ], 400);
            }
            $new_task_name = "Memindahkan item {$item->item_name} dari {$stationOutput->station_name} ke {$stationInput->station_name}";
        } else {
            // If neither station is provided, task name cannot be generated
            return response()->json([
                'status' => 'failed',
                'message' => 'Please provide either input or output station',
                'data' => null
            ], 400);
        }
        $task->task_name = $new_task_name;
        
        if($task->save()){
            return response()->json([
                'status' => 'success',
                'message' => "Task Data with code $task_code updated successfully",
                'data' => $task
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with code $task_code failed to update",
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with code $task_code failed to update",
                'data' => null
            ], 500);
        }
    }
    
 
    public function showById($id)
    {
        $task = Task::find($id); 
        $task_code = $task->task_code;

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with ID $id Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with ID $id Not Found",
            'data' => null
        ], 404);
    
    }

    public function showByName($task_name)
    {
        
        $task = Task::where('task_name', $task_name)->first();

        if (is_null($task)) {
            return response([
                'success' => false,
                'message' => "Data Task $task_name Not Found",
                'data' => null
            ], 404);
        }
       
        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data $task_name Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data $task_name Not Found",
            'data' => null
        ], 404);
    
    }

    public function showByCode($task_code)
    {
        $task = Task::where('task_code', $task_code)->first();

        if (is_null($task)) {
            return response([
                'success' => false,
                'message' => "Task Data with Code $task_code Not Found",
                'data' => null
            ], 404);
        }
       
        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Data with Code $task_code Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with Code $task_code Not Found",
            'data' => null
        ], 404);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyById($id)
    {
        $task = Task::find($id);

        if(is_null($task)){
            return response([
                'message' => "Task with ID $id Not Found",
                'data' => null
            ], 404);
        }

        if($task->delete()){
            return response([
                'message' => "Delete Task with ID $id Success",
                'data' => $task
            ], 200);
        }

        return response([
            'message' => "Delete Task with ID $id Failed",
            'data' => $task
        ], 400);
    }

    public function destroyByName($task_name)
    {
        $task = Task::where('task_name', $task_name)->first();

        if(is_null($task)){
            return response([
                'message' => "Task Data with Name $task_name Not Found",
                'data' => null
            ], 404);
        }

        if($task->delete()){
            return response([
                'message' => "Delete Task Data with Name $task_name Success",
                'data' => $task
            ], 200);
        }

        return response([
            'message' => "Delete $type_name Failed",
            'data' => $item_type
        ], 400);
    }

    public function destroyByCode($task_code)
    {
        $task = Task::where('task_code', $task_code)->first();

        if(is_null($task)){
            return response([
                'message' => "Task Data with Code $task_code Not Found",
                'data' => null
            ], 404);
        }

        if($task->delete()){
            return response([
                'message' => "Delete Task Data with Code $task_code Success",
                'data' => $task
            ], 200);
        }

        return response([
            'message' => "Delete $task_code Failed",
            'data' => $task
        ], 400);
    }
    
}
