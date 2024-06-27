<?php

namespace App\Handlers;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\AGV;
use Illuminate\Support\Facades\DB;

class WebsocketHandler implements MessageComponentInterface
{
    public function onOpen(ConnectionInterface $conn)
    {
        // New connection opened
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Handle incoming message
        $data = json_decode($msg, true);

        if (isset($data['type']) && $data['type'] == 'update') {
            foreach ($data['data'] as $agv) {
                // Validate and save data to database
                $record = AGV::updateOrCreate(
                    ['id' => $agv['id']],
                    [
                        'is_online' => $agv['isOnline'],
                        'power' => $agv['power'],
                        'position' => DB::raw("POINT({$agv['position']['x']}, {$agv['position']['y']})"),
                    ]
                );
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Connection closed
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Handle error
    }
}
