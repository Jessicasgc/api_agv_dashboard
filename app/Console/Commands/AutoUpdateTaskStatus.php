<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoUpdateTaskStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
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
                            // Check if container is true
                            if (isset($agvData['container']) && $agvData['container'] === true) {
                                Task::update(
                                    ['id' => $agvData['id']], // Unique identifier
                                    [
                                        'agv_name' => 'AGV '.$agvData['id'],
                                        'agv_status' => $agvData['isOnline'] ? 'online' : 'offline',
                                        'position' => \DB::raw("POINT({$agvData['position']['x']}, {$agvData['position']['y']})"),
                                        'power' => $agvData['power'],
                                        'speed' => $agvData['velocity']
                                    ]
                                );
                            }
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
