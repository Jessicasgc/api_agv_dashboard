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
                    $this->info("Received: {$msg}");
                    $data = json_decode($msg, true);
                    if (isset($data['type'])) {
                        switch ($data['type']) {
                            case 'NotifStart':
                                $this->handleNotifStart($data['data']);
                                // $this->info("Received: {$data}");
                                break;
                            case 'NotifEnd':
                                $this->handleNotifGoal($data['data']);
                                // $this->info("Received: {$data}");
                                break;
                        }
                    }
                });
                $this->allocateTasks($conn);
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
        $tasks = Task::whereNull('id_agv')->orderBy('id', 'asc')->get();
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

                if (($processingCount == 0 && $allocatedCount == 0) || ($totalCount > 0 && $totalCount < 5 && $stock < $destinationStation->max_capacity)) {
                    $distance = sqrt(pow($startStation->x - $agv->position['x'], 2) + pow($startStation->y - $agv->position['y'], 2));
                    
                    if($agvPower == 0){
                        $countFunction = 1000;
                    }else{
                        $countFunction = pow($distance, 2) + pow((250 / $agv->power), 2) + pow((2 * $totalCount / 5), 2) + pow($stock / 2, 2);
                    }

                    if ($countFunction < $minFunctionValue) {
                        $minFunctionValue = $countFunction;
                        $selectedAGV = $agv;
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
                $this->info("Send: {$dataToSendStation}");
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

            $task->id_start_station = $task->id_destination_station;
            $task->id_destination_station = null;
            $task->save();

            $item = Item::find($task->id_item);
            if ($item) {
                $item->id_station = $task->id_start_station;
                $item->save();
            }
        }
    }
     // if ($data['type'] === 'NotifStart') {
                    //     $this->updateTaskToProcessing($data['id_agv'], $data['task_code']);
                    // } elseif ($data['type'] === 'NotifEnd') {
                    //     $this->updateTaskToDone($data['id_agv'], $data['task_code']);
                    // }
    // private function updateTaskToProcessing($idAgv, $taskCode)
    // {
    //     $task = Task::where('task_code', $taskCode)->first();
    //     if ($task) {
    //         $task->task_status = 'processing';
    //         $task->start_time = now();
    //         $task->save();

    //         // Update stock start station -1
    //         $station = Station::find($task->id_start_station);
    //         if ($station) {
    //             $station->stock -= 1;
    //             $station->save();
    //         }
    //     }
    // }

    // private function updateTaskToDone($idAgv, $taskCode)
    // {
    //     $task = Task::where('task_code', $taskCode)->first();
    //     if ($task) {
    //         $task->status = 'done';
    //         $task->end_time = now();
    //         $task->save();

    //         // Update stock destination station +1
    //         $station = Station::find($task->id_destination_station);
    //         if ($station) {
    //             $station->stock += 1;
    //             $station->save();
    //         }

    //         // Set destination station to null and start station to destination station
    //         $task->id_destination_station = null;
    //         $task->id_start_station = $task->id_destination_station;
    //         $task->save();

    //         // Update item station
    //         $item = $task->id_item;
    //         if ($item) {
    //             $item->id_station = $task->id_start_station;
    //             $item->save();
    //         }
    //     }
    // }

    // private function allocateTasks()
    // {
    //     $tasks = Task::where('task_status', 'waiting')->orderBy('id', 'asc')->get();
    //     $agvs = AGV::all();

    //     foreach ($tasks as $task) {
    //         $allocatedAgv = $this->getBestAgvForTask($task, $agvs);
    //         if ($allocatedAgv) {
    //             $task->task_status = 'allocated';
    //             $task->id_agv = $allocatedAgv->id;
    //             $task->save();
    //             $startStation = Station::find($task->id_start_station);
    //             $this->sendDataToWebSocket([
    //                 'task_code' => $task->task_code,
    //                 'id_agv' => $allocatedAgv->id,
    //                 'goal_start' => [
    //                     // 'id_start_station' => $task->start_station_id,
    //                     'x' => $startStation->x,
    //                     'y' => $startStation->y,
    //                 ]
    //             ]);

    //             $destinationStation = Station::find($task->id_destination_station);
    //             $this->sendDataToWebSocket([
    //                 'task_code' => $task->task_code,
    //                 'id_agv' => $allocatedAgv->id,
    //                 'goal_destination' => [
    //                     // 'id_destination_station' => $task->destination_station_id,
    //                     'x' => $destinationStation->x,
    //                     'y' => $destinationStation->y,
    //                 ]
    //             ]);
    //         }
    //     }
    // }

    // private function getBestAgvForTask($task, $agvs)
    // {
    //     $bestAgv = null;
    //     $bestScore = PHP_INT_MAX;

    //     foreach ($agvs as $agv) {
    //         $distance = $this->calculateDistance($task, $agv);
    //         $agvPower = $agv->power;
    //         $jTask = Task::where('id_agv', $agv->id)->count();
    //         $destinationStation = Station::find($task->id_destination_station);
    //         $stock = $destinationStation->stock;

    //         $score = pow($distance, 2) + pow((250 / $agvPower), 2) + pow((2 * $jTask / 5), 2) + pow($stock / 2, 2);

    //         if ($score < $bestScore) {
    //             $bestScore = $score;
    //             $bestAgv = $agv;
    //         }
    //     }

    //     return $bestAgv;
    // }

    // private function calculateDistance($task, $agv)
    // {
    //     $startStation = Station::find($task->id_start_station);
    //     return sqrt(pow($startStation->x - $agv->position['x'], 2) + pow($startStation->y - $agv->position['y'], 2));
    // }

    // private function sendDataToWebSocket($conn, $data)
    // {
    //     $conn->send(json_encode($data));
    // }
    // private function sendDataToWebSocket($data)
    // {
    //     try {
    //         $client = new Client('ws://localhost:80/backend');
    //         $client->send(json_encode($data));
    //         $response = $client->receive();
    //         $client->close();
    //     } catch (\Exception $e) {
    //         \Log::error('Error connecting to WebSocket: ' . $e->getMessage());
    //     }
    // }
}

// $loop = Factory::create();
//         $connector = new Connector($loop, new ReactConnector($loop));

//         $connector('ws://localhost:80/dashboard')
//             ->then(function(WebSocket $conn) use ($loop) {
//                 $conn->on('message', function($msg) use ($conn) {
//                     $this->info("Received: {$msg}");
//                     $data = json_decode($msg, true);

//                     if (isset($data['type']) && $data['type'] === 'update' && isset($data['data'])) {
//                         foreach ($data['data'] as $agvData) {
//                             // Check if container is true
//                             if (isset($agvData['container']) && $agvData['container'] === true) {
//                                 Task::update(
//                                     ['id' => $agvData['id']], // Unique identifier
//                                     [
//                                         'agv_name' => 'AGV '.$agvData['id'],
//                                         'agv_status' => $agvData['isOnline'] ? 'online' : 'offline',
//                                         'position' => \DB::raw("POINT({$agvData['position']['x']}, {$agvData['position']['y']})"),
//                                         'power' => $agvData['power'],
//                                         'speed' => $agvData['velocity']
//                                     ]
//                                 );
//                             }
//                         }
//                     }
//                 });

//                 $conn->on('close', function($code = null, $reason = null) use ($loop) {
//                     $this->info("Connection closed ({$code} - {$reason})");
//                     $loop->stop();
//                 });
//             }, function($e) use ($loop) {
//                 $this->error("Could not connect: {$e->getMessage()}");
//                 $loop->stop();
//             });

//         $loop->run();