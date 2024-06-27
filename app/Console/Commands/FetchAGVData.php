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

class FetchAGVData extends Command
{
    protected $signature = 'fetch:agv';
    protected $description = 'Fetch AGV data from WebSocket service and store it in the database';
    protected $webSocketService;

    public function __construct(
        //WebsocketClient $webSocketService
        )
    {
        parent::__construct();
        // $this->webSocketService = $webSocketService;
    }

    // public function handle()
    // {
    //     $this->info('Starting WebSocket client...');
    //     $this->webSocketService->receiveData();
    // }

    public function handle()
    {
        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop));

        $connector('ws://localhost:80/dashboard')
            ->then(function(WebSocket $conn) use ($loop) {
                $conn->on('message', function($msg) use ($conn) {
                    $this->info("Received: {$msg}");
                    $data = json_decode($msg, true);

                    if (isset($data['type']) && $data['type'] === 'update' && isset($data['data'])) {
                        foreach ($data['data'] as $agvData) {
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




    // public function __construct()
    // {
    //     parent::__construct();
    // }
    // /**
    //  * The name and signature of the console command.
    //  *
    //  * @var string
    //  */
    // // protected $signature = 'command:name';

    // /**
    //  * The console command description.
    //  *
    //  * @var string
    //  */
    // // protected $description = 'Command description';

    // /**
    //  * Execute the console command.
    //  *
    //  * @return int
    //  */
    // public function handle()
    // {
    //     // return Command::SUCCESS;
    //     $wsClient = new WebSocketClient('ws://localhost:80/dashboard');

    //     // Request AGV data for AGV ID 1 (you can modify this to request for different IDs)
    //     $wsClient->send(json_encode(['type' => 'get_agv_data', 'agvId' => 1]));

    //     // Receive the response
    //     $response = $wsClient->receive();
    //     $wsClient->close();

    //     $msg = json_decode($response, true);

    //     if ($msg['type'] === 'agv') {
    //         $this->storeAGVData($msg['data']);
    //     }
    // }
    // protected function storeAGVData($agvData)
    // {
    //     $agvRecord = new AGV();
    //     $agvRecord->id = $agvData['id'];
    //     $agvRecord->is_online = $agvData['isOnline'];
    //     $agvRecord->container = $agvData['container'];
    //     $agvRecord->collision = $agvData['collision'];
    //     $agvRecord->power = $agvData['power'];
    //     $agvRecord->orientation = $agvData['orientation'];
    //     $agvRecord->acceleration = $agvData['acceleration'];
    //     $agvRecord->position = json_encode($agvData['position']);
    //     $agvRecord->paths = json_encode($agvData['paths']);
    //     $agvRecord->save();
    // }
}
