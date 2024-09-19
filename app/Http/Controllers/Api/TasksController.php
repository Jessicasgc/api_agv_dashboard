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
use App\Models\AGVTracking;
use Carbon\Carbon;
use Ratchet\Client\Connector as WebSocketConnector;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Connector as SocketConnector;
use App\Http\Controllers\WebSocketController;

class TasksController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.action')->only(['store', 'updateById', 'destroyById']);
    }
    public function index()
    {
        $tasks = Task::all();
        $tasksWithAgvData = $tasks->map(function ($task) {
            $agv = AGV::find($task->id_agv);
            if ($agv) {
                $task->agv_name = $agv->agv_name;
                $task->agv_code = $agv->agv_code; 
            } else {
                $task->agv_name = null;
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
        $allStation = Station::all();
        $allTask = Task::all();
        $countEachAGVProcessingAllocatedTasks = [];
        $countEachAGVWaitingTasks = [];
        $itemFind = Item::find($storeData['id_item']);
        $startStation = Station::find($itemFind->id_station);
        
        if ($startStation==null) {
            // Generating task name for item entering station
            $item = Item::find($storeData['id_item']);
            $storeData['id_start_station'] = 4;
            $destinationStation = Station::find($storeData['id_destination_station']);
            $task_name = "Put in {$item->item_code} to {$destinationStation->station_name}";
        } elseif ($storeData['id_destination_station'] === 4) {
            // Generating task name for item exiting station
            $item = Item::find($storeData['id_item']);
            $destinationStation = Station::find(4);
            $storeData['id_start_station'] = $startStation;
            $task_name = "Take out {$item->item_code} from {$startStation->station_name}";
        } elseif ($startStation!==null) {
            // Generating task name for item transferring between stations
            $item = Item::find($storeData['id_item']);
            $destinationStation = Station::find($storeData['id_destination_station']);
            $storeData['id_start_station'] = $startStation;
            $task_name = "Move the {$item->item_code} from {$startStation->station_name} to {$destinationStation->station_name}";
        } else {
            // If neither station is provided, task name cannot be generated
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid',
                'data' => null
            ], 400);
        }
    
        $storeData['task_name'] = $task_name;
        $waitingCount = Task::where('task_status', 'waiting')->count();
    
        // Loop through each AGV and count the tasks with the specified statuses
        foreach ($allAGVs as $agv) {
            $processingAllocatedCount = Task::where('id_agv', $agv->id)
                ->whereIn('task_status', ['processing', 'allocated'])
                ->count();
            
            $countEachAGVProcessingAllocatedTasks[$agv->id] = $processingAllocatedCount;
        } 
    
        $minFunctionValue = PHP_INT_MAX;
        $selectedAGVId = null;
    
        // Check the global condition outside of the foreach loop
        if ($waitingCount === 0) {
            foreach ($allAGVs as $agv) {
                $agvId = $agv->id;
                $processingAndAllocatedCount = $countEachAGVProcessingAllocatedTasks[$agvId] ?? 0;
    
                if ( $processingAndAllocatedCount < 5) {
                    if ($startStation === null) {
                        $startStation = Station::find(4);
                        $startX = $startStation->x;
                        $startY = $startStation->y;
                    } else {
                        $startX = $startStation->x;
                        $startY = $startStation->y;
                    }
                    
                    // Get power data from AGV
                    $agvPower = $agv->power;
                    \Log::info('AGV Position:', ['position' => $agv->position]);
                    $position = $agv->position;
                    $agvPosition = $agv->position;
                    $agvX = $agvPosition['x'];
                    $agvY = $agvPosition['y'];
    
                    // Calculate the function value for find assigned AGV
                    $distance = sqrt(pow($startX - $agvX, 2) + pow($startY - $agvY, 2));
                    $jTask = $processingAndAllocatedCount;
                    $stock = $destinationStation->stock;
                    
                    if($agvPower <= 20){
                        $countFunction = 1000;
                    }else{
                        $countFunction = pow($distance, 2) + pow((250 / $agvPower), 2) + pow((2 * $jTask / 5), 2) + pow($stock / 2, 2);
                    }
                    
                    // $onlineAGV = AGV::where('status', 'online')->count;
                    // Find the AGV with the minimum function value
                    if ($countFunction < $minFunctionValue) {
                        $minFunctionValue = $countFunction;
                        $selectedAGVId = $agvId;
                    }
                    // if($onlineAGV!=0){

                    // }
                }
            }
        }

    if (!is_null($selectedAGVId)) {
        $storeData['task_status'] = 'allocated';
        $task = Task::create([
            'id_start_station' =>  $startStation ? $startStation->id : $storeData['id_start_station'] ,
            'id_destination_station' => $storeData['id_destination_station'],
            'task_code' => $task_code,
            'task_name' => $storeData['task_name'],
            'id_agv' => $selectedAGVId,
            'id_item' => $storeData['id_item'],
            'task_status' => $storeData['task_status'],
            'start_time' => null,
            'end_time' => null
        ]);  
        $agvChoosen = AGV::find($selectedAGVId);
        $startStationLog = Station::find($task->id_start_station);
        $destinationStationLog = Station::find($task->id_destination_station);
        $agv1 = AGV::find(1);
        $agv2 = AGV::find(2);
        AGVTracking::create([
            'id_agv_choosen' =>  1,
            'agv_code_choosen' => $agvChoosen->agv_code,
            'agv_name_choosen' => $agvChoosen->agv_name,
            'agv_status_choosen' => $agvChoosen->agv_status,
            'position_choosen' => \DB::raw("POINT({$agvChoosen->position['x']}, {$agvChoosen->position['y']})"),
            'power_choosen' => $agvChoosen->power,
            'speed_choosen' => $agvChoosen->speed,
            'id_task' => $task->id,
            'start_station_name' => $startStationLog->station_name,
            'destination_station_name' => $destinationStationLog->station_name,
            'task_code' => $task->task_code,
            'task_name' => $task->task_name,
            'task_status' => $task->task_status,
            'item_name' => $itemFind->item_name,
            'start_time' => $task->start_time,
            'end_time' => $task->end_time,
            'id_agv_1' => 1,
            'agv_code_1' => $agv1->agv_code,
            'agv_name_1' => $agv1->agv_name,
            'agv_status_1' => $agv1->agv_status,
            'position_1' => \DB::raw("POINT({$agv1->position['x']}, {$agv1->position['y']})"),
            'power_1' => $agv1->power,
            'speed_1' => $agv1->speed,
            'id_agv_2' => 2,
            'agv_code_2' => $agv2->agv_code,
            'agv_name_2' => $agv2->agv_name,
            'agv_status_2' => $agv2->agv_status,
            'position_2' => \DB::raw("POINT({$agv2->position['x']}, {$agv2->position['y']})"),
            'power_2' => $agv2->power,
            'speed_2' => $agv2->speed,
        ]);
        

        $destinationStation = Station::find($storeData['id_destination_station']);
        if ($task) {
            if (!empty($startStation)) {
                $webSocketController = new WebSocketController();
                $dataToSendStation = [
                    'type' => 'task',
                    'data' => [
                        'task_code' => $task_code,
                        'id_agv' => $selectedAGVId,
                        'goal_start' => [
                            'x' => $selectedAGVId == 1 ? $startStation->x_agv1 : $startStation->x_agv2,
                            'y' => $selectedAGVId == 1 ? $startStation->y_agv1 : $startStation->y_agv2,
                        ],
                        'goal_destination' => [
                            'x' => $selectedAGVId == 1 ? $destinationStation->x_agv1 : $destinationStation->x_agv2,
                            'y' => $selectedAGVId == 1 ? $destinationStation->y_agv1 : $destinationStation->y_agv2,
                        ],
                    ]
                ];
                
                $webSocketController->sendDataToWebSocket($dataToSendStation);
            } else {
                $homestation = Station::find(4); 
                $webSocketController = new WebSocketController();
                $dataToSendHomestation = [
                    'type' => 'task',
                    'data' => [
                        'task_code' => $task_code,
                        'id_agv' => $selectedAGVId,
                        'goal_start' => [
                            'x' => $selectedAGVId == 1 ? $homestation->x_agv1: $homestation->x_agv2,
                            'y' => $selectedAGVId == 1 ? $homestation->y_agv1: $homestation->y_agv2,
                        ],
                        'goal_destination' => [
                            'x' => $selectedAGVId == 1 ? $destinationStation->x_agv1 : $destinationStation->x_agv2,
                            'y' => $selectedAGVId == 1 ? $destinationStation->y_agv1 : $destinationStation->y_agv2,
                        ],
                    ]
                ];
               
                $webSocketController->sendDataToWebSocket($dataToSendHomestation);
            }
           

            return response([
                'status' => 'success',
                'message' => "Create Data $task_code Task Success",
                'data' => $task,
                'assigned_agv_id' => $selectedAGVId
            ], 200);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => "Data $task_code failed to create",
                'data' => null
            ], 400);
        }
    } else {
       
        $storeData['task_status'] = 'waiting';
        $storeData['id_agv'] = null; 

        $task = Task::create([
            'id_start_station' => $startStation?$startStation->id:$storeData['id_start_station'],
            'id_destination_station' => $storeData['id_destination_station'],
            'task_code' => $task_code,
            'task_name' => $storeData['task_name'],
            'id_agv' => null,
            'id_item' => $storeData['id_item'],
            'task_status' => 'waiting',
            'start_time' => null,
            'end_time' => null,
        ]);
          
            if ($task) {
                return response([
                    'status' => 'success',
                    'message' => "Create Data $task_code Task Success",
                    'data' => $task,
                    'assigned_agv_id' => null
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
    }
 
    // public function updateById(Request $request, $id)
    // {
    //     $task = Task::find($id);

    //     if (is_null($task)) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => "Task Data with ID $id not found",
    //             'data' => null
    //         ], 404);
    //     }
    //     $updateData = $request->all();

    //     $validate = Validator::make($updateData, [
    //         'id_destination_station',
    //         'id_start_station',
    //         'id_item' => 'required',
    //     ]);
    //     if ($validate->fails()) {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'Validation Error',
    //             'data' => $validate->errors()
    //         ], 400);
    //     }
    //     // Generate task name based on the provided stations
    //     if (empty($updateData['id_start_station'])) {
    //         $item = Item::find($updateData['id_item']);
    //         $destinationStation = Station::find($updateData['id_destination_station']);
    //         if (!$destinationStation) {
    //             return response()->json([
    //                 'status' => 'failed',
    //                 'message' => 'Invalid input station ID provided',
    //                 'data' => null
    //             ], 400);
    //         }
    //         $new_task_name = "Put in item {$item->item_code} to {$destinationStation->station_name}";
    //     } elseif ($updateData['id_destination_station'] === 3) {
    //         $item = Item::find($updateData['id_item']);
    //         $startStation = Station::find($updateData['id_start_station']);
    //         if (!$startStation) {
    //             return response()->json([
    //                 'status' => 'failed',
    //                 'message' => 'Invalid output station ID provided',
    //                 'data' => null
    //             ], 400);
    //         }
    //         $new_task_name = "Take out item {$item->item_code} from {$startStation->station_name}";
    //     } elseif (!empty($updateData['id_start_station'])) {
    //         $item = Item::find($updateData['id_item']);
    //         $destinationStation = Station::find($updateData['id_destination_station']);
    //         $startStation = Station::find($updateData['id_start_station']);
    //         if (!$destinationStation || !$startStation) {
    //             return response()->json([
    //                 'status' => 'failed',
    //                 'message' => 'Invalid input or output station ID provided',
    //                 'data' => null
    //             ], 400);
    //         }
    //         $new_task_name = "Move item {$item->item_code} from {$startStation->station_name} to {$destinationStation->station_name}";
    //     } else {
    //         // If neither station is provided, task name cannot be generated
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => 'Please provide either input or output station',
    //             'data' => null
    //         ], 400);
    //     }
    //     $task->task_name = $new_task_name;
        
    //     if($task->save()){
    //         return response()->json([
    //             'status' => 'success',
    //             'message' => "Task Data with ID $id updated successfully",
    //             'data' => $task
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => "Task Data with ID $id failed to update",
    //             'data' => null
    //         ], 400);
    //         return response()->json([
    //             'status' => 'failed',
    //             'message' => "Task Data with ID $id failed to update",
    //             'data' => null
    //         ], 500);
    //     }
    // }

    
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

    public function showProcessingTasks()
    {
        $task = Task::where('task_status', 'processing')->get();

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with processing task status success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with processing task status Not Found",
            'data' => null
        ], 404);
    
    }
    public function showAllocatedTasks()
    {
        $task = Task::where('task_status', 'allocated')->get();

        if(!is_null($task)){
            return response([
                'success' => true,
                'message' => "Retrieve Task Data with allocated task status success",
                'data' => $task
            ], 200);
        }

        return response([
            'success' => false,
            'message' => "Task Data with allocated task status Not Found",
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

}
