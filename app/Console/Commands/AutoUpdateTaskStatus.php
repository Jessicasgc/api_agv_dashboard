<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\Socket\Connector as ReactConnector;
use App\Models\Task;
class AutoUpdateTaskStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'websocket:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for WebSocket notifications and update task statuses';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop));

        $connector('ws://localhost:80/dashboard')
            ->then(function(WebSocket $conn) use ($loop) {
                $conn->on('message', function($msg) use ($conn) {
                    $this->info("Received: {$msg}");
                    $data = json_decode($msg, true);

                    
                });

                $conn->on('close', function($code = null, $reason = null) use ($loop) {
                    $this->info("Connection closed ({$code} - {$reason})");
                    $loop->stop();
                });
            }, function($e) use ($loop) {
                $this->error("Could not connect: {$e->getMessage()}");
                $loop->stop();
            });

        $loop->run();


    }
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