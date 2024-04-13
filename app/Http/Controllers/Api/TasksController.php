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
use App\Models\AGV;
use Carbon\Carbon;


class TasksController extends Controller
{
    public function index()
    {
        $tasks = Task::all();
        $tasksWithAgvData = $tasks->map(function ($task) {
            // $agvName = AGV::find($task->id_agv)->agv_name;
            // $task->agv_name = $agvName;
            $agv = AGV::find($task->id_agv);
            if ($agv) {
                $task->agv_name = $agv->agv_name;
                $task->agv_code = $agv->agv_code; // Assuming 'agv_code' is the attribute name for AGV code
            } else {
                $task->agv_name = null; // Handle case where AGV is not found
                $task->agv_code = null;
            }
            return $task;
        });
    
        if ($tasksWithAgvData->count() > 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'Get Tasks successfully',
                'data' => $tasksWithAgvData
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

        $allAGVs = AGV::all();

        // Mendapatkan AGV yang belum mencapai batas maksimum tugas
        $availableAGVs = collect([]);
        
        foreach ($allAGVs as $agv) {
            $agvTasksCounts = Task::where('id_agv', $agv->id)
                                  ->whereNotNull('id_agv')
                                  ->whereIn('task_status', ['processing', 'allocated', 'done'])
                                  ->count();
            
            if ($agvTasksCounts < 5) { // Cek apakah AGV masih bisa menampung tugas dengan status allocated
                $availableAGVs->push($agv);
            }
        }
        
        // Memilih AGV secara acak dari AGV yang tersedia
        $availableAGV = $availableAGVs->isEmpty() ? null : $availableAGVs->random();
        
        if ($availableAGV) {
            // Menghitung jumlah tugas yang sudah dialokasikan untuk AGV yang dipilih
            $agvTasksCounts = Task::where('id_agv', $availableAGV->id)
                                  ->whereNotNull('id_agv')
                                  ->whereIn('task_status', ['processing', 'allocated', 'done'])
                                  ->count();
            var_dump($agvTasksCounts);
            if ($agvTasksCounts == 0) {
                // Jika belum ada tugas yang terkait dengan AGV tersebut,
                // status tugas diatur menjadi "processing" dan id_agv diisi dengan ID AGV
                $storeData['task_status'] = 'processing';
                $storeData['id_agv'] = $availableAGV->id;
            } else if ($agvTasksCounts > 0 && $agvTasksCounts <= 4) {
                // Jika jumlah tugas untuk AGV tersebut belum mencapai maksimum,
                // tugas akan dialokasikan ke AGV tersebut
                $storeData['task_status'] = 'allocated';
                $storeData['id_agv'] = $availableAGV->id;
            }
        } else {
            // Jika tidak ada AGV yang tersedia, status tugas diatur menjadi "waiting"
            $storeData['task_status'] = 'waiting';
            $storeData['id_agv'] = null; // Set AGV ID to null
        }
        

        // Set start time and end time based on task status
        if($storeData['task_status'] == "waiting" || $storeData['task_status'] == "allocated") {
            $storeData['start_time'] = null;
            $storeData['end_time'] = null;
        } else if ($storeData['task_status'] == "processing") {
            $storeData['start_time'] = Carbon::now();
        } else if ($storeData['task_status'] == "done") {
            $storeData['end_time'] = Carbon::now();
        }

        if (!empty($storeData['id_station_input']) && empty($storeData['id_station_output'])) {
            // Generating task name for item entering station
            $item = Item::find($storeData['id_item']);
            $stationInput = Station::find($storeData['id_station_input']);
            $task_name = "Put in {$item->item_name} to {$stationInput->station_name}";
        } elseif (empty($storeData['id_station_input']) && !empty($storeData['id_station_output'])) {
            // Generating task name for item exiting station
            $item = Item::find($storeData['id_item']);
            $stationOutput = Station::find($storeData['id_station_output']);
            $task_name = "Take out {$item->item_name} from {$stationOutput->station_name}";
        } elseif (!empty($storeData['id_station_input']) && !empty($storeData['id_station_output'])) {
            // Generating task name for item transferring between stations
            $item = Item::find($storeData['id_item']);
            $stationInput = Station::find($storeData['id_station_input']);
            $stationOutput = Station::find($storeData['id_station_output']);
            $task_name = "Move the {$item->item_name} from {$stationOutput->station_name} to {$stationInput->station_name}";
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
            'id_agv',
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            'task_status' => 'required',
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

        if (is_null($task)) {
            return response()->json([
                'status' => 'failed',
                'message' => "Task Data with ID $id not found",
                'data' => null
            ], 404);
        }
        $updateData = $request->all();

        $validate = Validator::make($updateData, [
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            //'task_status' => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Validation Error',
                'data' => $validate->errors()
            ], 400);
        }

    //  // Check if there are no tasks processing and the AGV is empty
    //  $processingTasksCount = Task::where('task_status', 'processing')->count();
    //  $emptyAGVsCount = AGV::whereDoesntHave('tasks', function ($query) {
    //      $query->where('task_status', 'processing');
    //  })->count();
 
    //  // Automatically update task status to "processing" if no tasks are processing and AGV is empty
    //  if ($processingTasksCount == 0 && $emptyAGVsCount == AGV::count()) {
    //      $updateData['task_status'] = 'processing';
    //  }
 
    //  // Check if there is a processing task on another AGV and this AGV has no processing tasks and no tasks at all
    //  $otherProcessingTasksCount = Task::where('task_status', 'processing')
    //      ->where('id_agv', '<>', $task->id_agv)
    //      ->count();
    //  $thisAGVProcessingTasksCount = Task::where('task_status', 'processing')
    //      ->where('id_agv', $task->id_agv)
    //      ->count();
 
    //  if ($otherProcessingTasksCount > 0 && $thisAGVProcessingTasksCount == 0 && $emptyAGVsCount > 0) {
    //      $updateData['task_status'] = 'processing';
    //  }
 
    //  // Check if all AGVs have processing tasks and this AGV has fewer than or equal to 5 allocated tasks
    //  $allocatedTasksCount = Task::where('task_status', 'allocated')
    //      ->where('id_agv', $task->id_agv)
    //      ->count();
 
    //  if ($processingTasksCount > 0 && $allocatedTasksCount <= 5) {
    //      $updateData['task_status'] = 'allocated';
    //  }
 
    //  // If there are more than 5 allocated tasks in each AGV, prevent updating to allocated status
    //  if ($allocatedTasksCount > 5 && $updateData['task_status'] == 'allocated') {
    //      $updateData['task_status'] = 'waiting';
    //  }

    // // Set start time and end time based on task status
    // if ($updateData['task_status'] == "waiting" || $updateData['task_status'] == "allocated") {
    //     $updateData['start_time'] = null;
    //     $updateData['end_time'] = null;
    // } else if ($updateData['task_status'] == "processing") {
    //     $updateData['start_time'] = Carbon::now();
    // } else if ($updateData['task_status'] == "done") {
    //     $updateData['end_time'] = Carbon::now();
    // }
    //     $task->id_agv = ($updateData['task_status'] == 'waiting') ? null : $updateData['id_agv'];
    //     $task->id_station_input = $updateData['id_station_input'];
    //     $task->id_station_output = $updateData['id_station_output'];
    //     $task->id_item = $updateData['id_item'];
    //     $task->task_status = $updateData['task_status'];
       
    //     if (isset($updateData['task_status']) && $updateData['task_status'] == "processing") {
    //         $updateData['start_time'] = Carbon::now();
    //     } else if (isset($updateData['task_status']) && $updateData['task_status'] == "done") {
    //         $updateData['end_time'] = Carbon::now();
    //     } 

    //     if (!is_null($task->end_time)) {
    //         $task->task_status = "done";
    //     }
       

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
            $new_task_name = "Put in item {$item->item_name} to {$stationInput->station_name}";
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
            $new_task_name = "Take out item {$item->item_name} from {$stationOutput->station_name}";
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
            $new_task_name = "Move item {$item->item_name} from {$stationOutput->station_name} to {$stationInput->station_name}";
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

        $validAgvStatuses = ["waiting", "allocated", "processing", "done"];

        // Validate AGV status
        if (isset($updateData['task_status']) && !in_array($updateData['task_status'], $validAgvStatuses)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid AGV status. Allowed values are waiting, allocated, processing, done.',
                'data' => null
            ], 400);
        }

        $validate = Validator::make($updateData, [
            'id_agv' => 'required',
            'id_station_input',
            'id_station_output',
            'id_item' => 'required',
            'task_status' => 'required',
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
        $task->task_status = $updateData['task_status'];
        if (isset($updateData['task_status']) && $updateData['task_status'] == "processing") {
            $updateData['start_time'] = Carbon::now();
        } elseif (isset($updateData['task_status']) && $updateData['task_status'] == "done") {
            $updateData['end_time'] = Carbon::now();
        }
        //$task->fill($updateData)->save();
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
            $new_task_name = "Put in item {$item->item_name} to {$stationInput->station_name}";
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
            $new_task_name = "Take out item {$item->item_name} from {$stationOutput->station_name}";
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
            $new_task_name = "Move item {$item->item_name} from {$stationOutput->station_name} to {$stationInput->station_name}";
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

    public function showWaitingTasks()
    {
        $task = Task::where('task_status', 'waiting')->get();

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with waiting task status success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with waiting task status Not Found",
            'data' => null
        ], 404);
    
    }

    public function showDoneTasks()
    {
        $task = Task::where('task_status', 'done')->get();

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with waiting task status success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with waiting task status Not Found",
            'data' => null
        ], 404);
    
    }

    public function showByIdAGV($id_agv)
    {
        $task = Task::where('id_agv', $id_agv)->get();
        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with ID $id_agv Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with ID $id_agv Not Found or doesn't have id_agv",
            'data' => null
        ], 404);
    }

    public function showProcessingByIdAGV($id_agv)
    {
        $task = Task::where('id_agv', $id_agv)
        ->where('task_status', 'processing')
        ->get();

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with ID $id_agv Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with ID $id_agv Not Found or doesn't have id_agv",
            'data' => null
        ], 404);
    }

    public function showAllocatedByIdAGV($id_agv)
    {
        $task = Task::where('id_agv', $id_agv)
        ->where('task_status', 'allocated')
        ->get();
        
        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with ID $id_agv Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with ID $id_agv Not Found or doesn't have id_agv",
            'data' => null
        ], 404);
    }

    public function showDoneByIdAGV($id_agv)
    {
        $task = Task::where('id_agv', $id_agv)
        ->where('task_status', 'done')
        ->get();
        
        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with ID $id_agv Success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with ID $id_agv Not Found or doesn't have id_agv",
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
            'data' => $task
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
