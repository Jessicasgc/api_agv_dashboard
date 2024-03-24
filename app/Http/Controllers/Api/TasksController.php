<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Task;
use Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;

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
        $uuid_task = IdGenerator::generate(['table' => 'tasks', 'field' => 'uuid_task', 'length' => 10, 'prefix' => date('Task-')]);
        $storeData['uuid_task'] = $uuid_task;
        

        Validator::extend('one_nullable', function ($attribute, $value, $parameters, $validator) {
            $otherAttribute = $parameters[0];
        
            $otherValue = $validator->getData()[$otherAttribute];
        
            return ($value !== null || $otherValue !== null) && ($value === null || $otherValue === null);
        });

        $validate = Validator::make($storeData, [
            'uuid_task' => 'required'|'regex:/^Task-\d{10}$/'|'unique:tasks',
            'id_agv' => 'required',
            'id_station_input' => 'sometimes|required|nullable|one_nullable:id_station_output',
            'id_station_output' => 'sometimes|required|nullable|one_nullable:id_station_input',
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
                'message' => 'Create Data $uuid_task Task Success',
                'data' => $task
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_task failed to create',
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_task failed to create',
                'data' => null
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        $uuid_task = $task->uuid_task;
        
        if(is_null($task)){
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_task not found',
                'data' => null
            ], 404);
        }
        $updateData = $request->all();
        Validator::extend('one_nullable', function ($attribute, $value, $parameters, $validator) {
            $otherAttribute = $parameters[0];
        
            $otherValue = $validator->getData()[$otherAttribute];
        
            return ($value !== null || $otherValue !== null) && ($value === null || $otherValue === null);
        });

        $validate = Validator::make($updateData, [
            'id_station_input' => 'sometimes|required|nullable|one_nullable:id_station_output',
            'id_station_output' => 'sometimes|required|nullable|one_nullable:id_station_input',
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
            
        $task->id_station_input = $updateData['id_station_input'];
        $task->id_station_output = $updateData['id_station_output'];
        $task->id_item = $updateData['id_item'];
        $task->task_status = $updateData['task_status'];
        $task->start_time = $updateData['start_time'];
        $task->end_time = $updateData['end_time'];

        if($task->save()){
            return response()->json([
                'status' => 'success',
                'message' => 'Data $uuid_task updated successfully',
                'data' => $task
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_task failed to update',
                'data' => null
            ], 400);
            return response()->json([
                'status' => 'failed',
                'message' => 'Data $uuid_task failed to update',
                'data' => null
            ], 500);
        }
        }
    }
 
    public function show($id)
    {
        $task = Task::find($id); 
        $uuid_task = $task->uuid_task;

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => 'Retrieve Data $uuid_task Success',
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => '$uuid_task Not Found',
            'data' => null
        ], 404);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::find($id);
        $uuid_task = $task->uuid_task;

        if(is_null($task)){
            return response([
                'message' => '$uuid_task Not Found',
                'data' => null
            ], 404);
        }

        if($task->delete()){
            return response([
                'message' => 'Delete $uuid_task Success',
                'data' => $task
            ], 200);
        }

        return response([
            'message' => 'Delete $uuid_task Failed',
            'data' => $task
        ], 400);
    }
    
}
