<?php

namespace App\Services;

// use Ratchet\Client\WebSocket;
// use Ratchet\Client\Connector;
// use React\EventLoop\Factory as LoopFactory;
// use React\Socket\Connector as ReactConnector;
// use React\Promise\Deferred;
// use App\Models\AGV;
use WebSocket\Client;
use WebSocket\ConnectionException;
use React\EventLoop\Factory as LoopFactory;
use React\Promise\Deferred;
use App\Models\AGV;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class WebsocketClient
{
    protected $client;
    public function __construct()
    {
        // Initialize the WebSocket client with your WebSocket server URL
        $this->client = new Client("ws://localhost:80/dashboard");
    }

    public function receiveData()
    {
        while (true) {
            try {
                $message = $this->client->receive();
                // $decodedMessage = json_decode($message, true);
// Show the message in the terminal
                    
            
            if (strpos($message, '"type":"update"') == true) {
                // Show the message in the terminal
                echo $message . PHP_EOL;
                $this->saveData($message);
            }
            } catch (\Exception $e) {
                // Handle WebSocket connection error
                \Log::error("WebSocket connection error: " . $e->getMessage());
            }
            sleep(1); // Wait for 1 second before receiving the next message
        }
    }

    protected function saveData($data)
    {
        $dataArray = json_decode($data, true);

        // Check if the "data" key exists and contains an array
    if (isset($decodedData['type'], $decodedData['data']) && $decodedData['type'] === 'update') {
        // Iterate over the "data" array
        foreach ($dataArray['data'] as $agvData) {
            // Check if the necessary keys exist in the AGV data
            if (isset($agvData['id'], $agvData['isOnline'], $agvData['power'], $agvData['position']['x'], $agvData['position']['y'])) {
                // Extract required data
                $agvId = $agvData['id'];
                $isOnline = $agvData['isOnline'] == 1 ? 'online' : 'offline';
                $power = $agvData['power'];
                // $position = $agvData['position']['x'] . ',' . $agvData['position']['y'];
                $positionX = $agvData['position']['x'];
                $positionY = $agvData['position']['y'];

                // Check if AGV with the same ID exists
                $agv = AGV::find($agvId);
                if ($agv) {
                    // Update existing AGV
                    $agv->update([
                        'agv_status' => $isOnline,
                        'power' => $power,
                        'position' => DB::raw("POINT($positionX, $positionY)"),
                    ]);
                    Log::info("AGV successfully updated to the database: ID - $agvId");
                } else {
                    // Create new AGV
                    AGV::create([
                        'id' => $agvId,
                        'agv_name' => 'AGV - ' . $agvId,
                        'agv_code' => 'AGV-' . Str::uuid()->toString(),
                        'agv_status' => $isOnline,
                        'position' => DB::raw("POINT($positionX, $positionY)"),
                        'power' => $power,
                    ]);
                    Log::info("AGV successfully saved to the database: ID - $agvId");
                }
            } else {
                Log::error("Incomplete AGV data: " . print_r($agvData, true));
            }
        }
    } else {
        Log::error("Invalid WebSocket message format: " . $data);
    }
    }


    // protected $loop;

    // public function __construct($url)
    // {
    //     $this->client = new Client($url);
    //     $this->loop = LoopFactory::create();
    // }

    // public function send($data)
    // {
    //     $this->client->send(json_encode($data));
    // }

    // public function receive()
    // {
    //     try {
    //         $message = $this->client->receive();
    //         $decodedMessage = json_decode($message, true);

    //         if (isset($decodedMessage['type']) && $decodedMessage['type'] === 'update') {
    //             return $decodedMessage['data'];
    //         }
    //         return $decodedMessage;
    //     } catch (ConnectionException $e) {
    //         throw new \Exception("WebSocket connection error: " . $e->getMessage());
    //     }
    // }

    // public function receiveOnce(callable $callback)
    // {
    //     try {
    //         $data = $this->receive();
    //         if ($data) {
    //             $callback($data);
    //         }
    //     } catch (ConnectionException $e) {
    //         // Log the error and handle it
    //         \Log::error("WebSocket connection error: " . $e->getMessage());
    //     }
    // }

    // public function receiveEverySecond(callable $callback)
    // {
    //     $this->loop->addPeriodicTimer(1, function () use ($callback) {
    //         try {
    //             $data = $this->receive();
    //             if ($data) {
    //                 $callback($data);
    //             }
    //         } catch (ConnectionException $e) {
    //             // Log the error and continue
    //             \Log::error("WebSocket connection error: " . $e->getMessage());
    //         }
    //     });

    //     $this->loop->run();
    // }
    // protected $client;

    // public function __construct($url)
    // {
    //     $this->client = new Client($url);
    // }

    // public function send($data)
    // {
    //     $this->client->send(json_encode($data));
    // }

    // public function receive()
    // {
    //     try {
    //         $message = $this->client->receive();
    //         $decodedMessage = json_decode($message, true);

    //         if (isset($decodedMessage['type']) && $decodedMessage['type'] === 'update') {
    //             return $decodedMessage['data'];
    //         }
    //         return null;
    //     } catch (\Websocket\ConnectionException $e) {
    //         throw new \Exception("WebSocket connection error: " . $e->getMessage());
    //     }
    // }

    // public function receiveEverySecond(callable $callback)
    // {
    //     while (true) {
    //         try {
    //             $data = $this->receive();
    //             if ($data) {
    //                 $callback($data);
    //             }
    //         } catch (\Websocket\ConnectionException $e) {
    //             throw new \Exception("WebSocket connection error: " . $e->getMessage());
    //             sleep(1);
    //             continue;
    //         }

    //         sleep(0.5); // wait for 1 second before receiving the next message
    //     }
    // }
    
    // public function close()
    // {
    //     $this->client->close();
    // }
    // protected $url;
    // protected $dataCallback;

    // public function __construct($url)
    // {
    //     $this->url = $url;
    // }

    // public function fetchAGVData(callable $dataCallback)
    // {
    //     $this->dataCallback = $dataCallback;
    //     $loop = LoopFactory::create();
    //     $reactConnector = new ReactConnector($loop);
    //     $connector = new Connector($loop, $reactConnector);

    //     $connector($this->url)->then(function (WebSocket $conn) {
    //         $conn->on('message', function ($msg) use ($conn) {
    //             $data = json_decode($msg, true);
    //             if (is_callable($this->dataCallback)) {
    //                 call_user_func($this->dataCallback, $data);
    //             }
    //             $conn->close();
    //         });

    //         $conn->on('close', function ($code = null, $reason = null) {
    //             echo "Connection closed ({$code} - {$reason})\n";
    //         });
    //     }, function (\Exception $e) {
    //         echo "Could not connect: {$e->getMessage()}\n";
    //     });

    //     $loop->run();
    // }


    // public function connect()
    // {
    //     $loop = LoopFactory::create();
    //     $reactConnector = new ReactConnector($loop);
    //     $connector = new Connector($loop, $reactConnector);

    //     $connector($this->url)->then(function ($conn) {
    //         $conn->on('message', function ($msg) use ($conn) {
    //             $this->handleMessage($msg);
    //         });

    //         $conn->on('close', function ($code = null, $reason = null) {
    //             echo "Connection closed ({$code} - {$reason})\n";
    //         });
    //     }, function (Exception $e) {
    //         echo "Could not connect: {$e->getMessage()}\n";
    //     });

    //     $loop->run();
    // }

    // protected function handleMessage($msg)
    // {
    //     // Process the incoming message
    //     $data = json_decode($msg, true);

    //     // Example: Save data to the database
    //     AGV::updateOrCreate(
    //         ['id' => $data['id']],
    //         [
    //             'agv_status' => $data['agv_status'],
    //             // 'is_charging' => $data['is_charging'],
    //             'position' => $data['position'],
    //             // 'container' => $data['container'],
    //             // 'collision' => $data['collision'],
    //             'power' => $data['power'],
    //             // 'orientation' => $data['orientation'],
    //             // 'acceleration' => json_encode($data['acceleration']),
    //             // 'localMap' => json_encode($data['localMap']),
    //         ]
    //     );
    //     Event::dispatch(new WebsocketEvent($data));
    // }
}