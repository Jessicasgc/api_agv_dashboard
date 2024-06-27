<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Traits\WebsocketTrait;

class WebsocketEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels, WebsocketTrait;
    
    public $stationData;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($stationData)
    {
        $this->stationData = $stationData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        $this->agvData = $this->store();
        return [new Channel('station-coordinates')];
    }

    public function broadcastWith()
    {
        //return ['data' => $this->agvData];
        return [
            'id' => $this->station->id,
            'x' => $this->station->x,
            'y' => $this->station->y,
        ];
    }
}
