<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use App\Models\Task;
use App\Models\Station;
use App\Models\AGV;
use App\Models\Item;
use App\Models\AGVTracking;

class AutoUpdateTaskStatus extends Command
{
    protected $signature = 'autoupdate:tasks';

    protected $description = 'Listen for WebSocket notifications and update task statuses';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop));

        $connector('ws://localhost:80/backend')
            ->then(function(WebSocket $conn) use ($loop) {
                $conn->on('message', function($msg) use ($conn) {
                    // $this->info("Received: {$msg}");
                    $data = json_decode($msg, true);
                    if (isset($data['type'])) {
                        switch ($data['type']) {
                            case 'NotifStart':
                                $this->handleNotifStart($data['data']);
                                break;
                            case 'NotifEnd':
                                $this->handleNotifGoal($data['data']);
                                break;
                        }
                    }
                });
                $loop->addPeriodicTimer(1, function() use ($conn) {
                    $this->allocateTasks($conn);
                });
                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    $this->info("Connection closed ({$code} - {$reason})");
                    $loop->stop();
                });
            },
             function($e) use ($loop) {
                $this->error("Could not connect: {$e->getMessage()}");
                $loop->stop();
            }
            );
        $loop->run();
    }

    protected function allocateTasks(WebSocket $conn)
    {
        $tasks = Task::where('task_status','waiting')->orderBy('id', 'asc')->get();
        $agvs = AGV::all();

        foreach ($tasks as $task) {
            $startStation = Station::find($task->id_start_station) ?? Station::find(3);
            $destinationStation = Station::find($task->id_destination_station);
            $stock = $destinationStation->stock;
            $minFunctionValue = PHP_INT_MAX;
            $selectedAGV = null;

            foreach ($agvs as $agv) {
                $processingCount = Task::where('id_agv', $agv->id)->where('task_status', 'processing')->count();
                $allocatedCount = Task::where('id_agv', $agv->id)->where('task_status', 'allocated')->count();
                $totalCount = $processingCount + $allocatedCount;
                $this->info("AGV ID: " . $agv->id . " | Processing: " . $processingCount . " | Allocated: " . $allocatedCount . " | Total: " . $totalCount);
                                $this->info("AGV ID: " . $agv->id . " | Processing: " . $processingCount . " | Allocated: " . $allocatedCount . " | Total: " . $totalCount);
                if ($totalCount < 5 && $stock < $destinationStation->max_capacity) {
                    $distance = sqrt(pow($startStation->x - $agv->position['x'], 2) + pow($startStation->y - $agv->position['y'], 2));
                    
                    $this->info("AGV ID: " . $agv->id . " | Distance: " . $distance . " | Power: " . $agv->power);
                    if($agv->power < 20 ){
                        $countFunction = 1000;
                    }else{
                        $countFunction = pow($distance, 2) + pow((250 / $agv->power), 2) + pow((2 * $totalCount / 5), 2) + pow($stock / 2, 2);
                    }
                    $this->info("AGV ID: " . $agv->id . " | Count Function: " . $countFunction);
                    if ($countFunction < $minFunctionValue) {
                        $minFunctionValue = $countFunction;
                        $selectedAGV = $agv;
                        $this->info("AGV ID: " . $selectedAGV . " | Count Function: " . $countFunction);
                    }else if($countFunction==$minFunctionValue){
                        $randomAGV = $agvs->rand();
                        $selectedAGV = $randomAGV;
                        $this->info("AGV ID: " . $randomAGV . " | Count Function: " . $countFunction);
                    }
                    
                }
            }

            if ($selectedAGV) {
                $task->id_agv = $selectedAGV->id;
                $task->task_status = 'allocated';
                $task->save();

                $dataToSendStation = [
                    'type' => 'task',
                    'data' => [
                        'task_code' => $task->task_code,
                        'id_agv' => $selectedAGV->id,
                        'goal_start' => [
                            'x' => $selectedAGV == 1 ? $startStation->x_agv1 : $startStation->x_agv2,
                            'y' => $selectedAGV == 1 ? $startStation->y_agv1 : $startStation->y_agv2,
                        ],
                        'goal_destination' => [
                            'x' => $destinationStation->x,
                            'y' => $destinationStation->y,
                        ],

                    ]
                ];
                $conn->send(json_encode($dataToSendStation));
                $this->info("Send: " . json_encode($dataToSendStation));

                $agvChoosen = AGV::find($task->id_agv);
                $itemFind = Item::find($task->id_item);
                $startStationLog = Station::find($task->id_start_station);
                $destinationStationLog = Station::find($task->id_destination_station);
                $itemFind = Item::find($task->id_item);
                $agv1 = AGV::find(1);
                $agv2 = AGV::find(2);
                AGVTracking::create([
                    'id_agv_choosen' => $agvChoosen->id,
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
            }
        }
    }
    protected function handleNotifStart($data)
    {
        $task = Task::where('task_code', $data['task_code'])->first();
        if ($task) {
            $task->task_status = 'processing';
            $task->start_time = now();
            $task->save();

            $startStation = Station::find($task->id_start_station);
            if ($startStation) {
                if($startStation->stock > 0){
                    $startStation->stock -= 1;
                    $startStation->save();
                }
                
            }
            $agvChoosen = AGV::find($task->id_agv);
            $startStationLog = Station::find($task->id_start_station);
            $destinationStationLog = Station::find($task->id_destination_station);
            $itemFind = Item::find($task->id_item);
            $agv1 = AGV::find(1);
            $agv2 = AGV::find(2);
            AGVTracking::create([
                'id_agv_choosen' =>  $agvChoosen->id,
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
        }
    }

    protected function handleNotifGoal($data)
    {
        $task = Task::where('task_code', $data['task_code'])->first();
        if ($task) {
            $task->task_status = 'done';
            $task->end_time = now();
            $task->save();

            $destinationStation = Station::find($task->id_destination_station);
            if ($destinationStation) {
                $destinationStation->stock += 1;
                $destinationStation->save();
            }

            $item = Item::find($task->id_item);
            if ($item) {
                $item->id_station = $task->id_destination_station;
                $item->save();
            }
            $agvChoosen = AGV::find($task->id_agv);
            $startStationLog = Station::find($task->id_start_station);
            $destinationStationLog = Station::find($task->id_destination_station);
            
            $agv1 = AGV::find(1);
            $agv2 = AGV::find(2);
            AGVTracking::create([
                'id_agv_choosen' =>  $agvChoosen->id,
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
                'item_name' => $item->item_name,
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
        }
    }
}