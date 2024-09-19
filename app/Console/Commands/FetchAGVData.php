<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\Client\WebSocket;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\Socket\Connector as ReactConnector;
use App\Services\WebsocketClient;
// use WebSocket\Client as WebSocketClient;
 use App\Models\AGV;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FetchAGVData extends Command
{
    protected $signature = 'fetch:agv';
    protected $description = 'Fetch AGV data from WebSocket service and store it in the database';
    protected $webSocketService;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop));
        $uuid = Str::uuid();
        $uniqueCode = substr($uuid, 0, 8); 
        $connector('ws://localhost:80/backend')
            ->then(function(WebSocket $conn) use ($loop) {
                $conn->on('message', function($msg) use ($conn) {
                    $this->info("Received: {$msg}");
                    $data = json_decode($msg, true);

                    if (isset($data['type']) && $data['type'] === 'update' && isset($data['data'])) {
                        foreach ($data['data'] as $agvData) {
                            $agv = AGV::firstOrNew(['id' => $agvData['id']]);

                            // Generate a unique code if the record is new
                            if (!$agv->exists) {
                                $uuid = Str::uuid();
                                $uniqueCode = substr($uuid, 0, 8);
                                $agv->agv_code = 'AGV' . $uniqueCode;
                            }
                            AGV::updateOrCreate(
                                

                                ['id' => $agvData['id']], // Unique identifier
                                [
                                    'agv_name' => 'AGV '.$agvData['id'],
                                    
                                    'agv_status' => $agvData["isOnline"] ? 'online' : 'offline',
                                    'position' => \DB::raw("POINT({$agvData['position']['x']}, {$agvData['position']['y']})"),
                                    'power' => $agvData['power'],
                                    'speed' => $agvData['velocity']
                                ]
                            );
                           
                        }
                    }
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
