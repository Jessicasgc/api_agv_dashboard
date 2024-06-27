<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AGV;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\WebsocketClient;

class ReceiveAgvData implements ShouldQueue
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        // Store data to the database
        AGV::create($this->data);
    }
    //  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // //protected $webSocketClient;
    // protected $url;
    // // public function __construct(WebsocketClient $webSocketClient)
    // // {
    // //     $this->webSocketClient = $webSocketClient;
    // // }
    // public function __construct($url)
    // {
    //     $this->url = $url;
    // }
    // public function handle()
    // {
    //     $webSocketClient = new WebsocketClient($this->url);
    //     $webSocketClient->receiveEverySecond(function($agvData) {
    //         foreach ($agvData as $agv) {
    //             if (isset($agvData['id'])) {
    //                 $agvRecord = new AGV();
    //                 $uuid = Str::uuid();
    //                 $uniqueCode = substr($uuid, 0, 8);
    //                 $agvCode = 'AGV-' . $uniqueCode;
    //                 $agvRecord->id = $agvData['id'];
    //                 $agvRecord->agv_name = 'AGV' . $agvData['id'];
    //                 $agvRecord->agv_code = $agvCode;
    //                 $agvRecord->agv_status = $agvData['isOnline'];
    //                 $agvRecord->position = DB::raw("POINT({$agvData['position']['x']}, {$agvData['position']['y']})");
    //                 $agvRecord->power = $agvData['power'];
    //                 $agvRecord->save();
    //             }
    //         }
    //     });
    //     $webSocketClient->close();
    // }
    // // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels {
    //     __serialize as private serializesModels;
    //     __unserialize as private unserializesModels;
    // }
    // /**
    //  * Create a new job instance.
    //  *
    //  * @return void
    //  */
    
    // protected $webSocketClient;
    // private $webSocketClientNotSerializable;
    // public function __construct()
    // {
    //     // Placeholder to avoid serialization issues
    // }
    // public function injectWebSocketClient(WebsocketClient $webSocketClient)
    // {
    //     $this->webSocketClient = $webSocketClient;
    // }

    // /**
    //  * Execute the job.
    //  *
    //  * @return void
    //  */
    // public function handle(WebsocketClient $webSocketClient)
    // {
    //     $webSocketClient = $this->webSocketClientNotSerializable ?? $webSocketClient;

    //     $webSocketClient->receiveEverySecond(function($agvData) {
    //         if (isset($agvData['id'])) {
    //             $agvRecord = new AGV();
    //             $uuid = Str::uuid();
    //             $uniqueCode = substr($uuid, 0, 8);
    //             $agvCode = 'AGV-' . $uniqueCode;
    //             $agvRecord->id = $agvData['id'];
    //             $agvRecord->agv_name = 'AGV' . $agvData['id'];
    //             $agvRecord->agv_code = $agvCode;
    //             $agvRecord->agv_status = $agvData['isOnline'];
    //             $agvRecord->position = DB::raw("POINT({$agvData['position']['x']}, {$agvData['position']['y']})");
    //             $agvRecord->power = $agvData['power'];
    //             $agvRecord->save();
    //         }
    //     });
    // }
    //     public function __serialize(): array
    //     {
    //         return $this->serializesModels();
    //     }
    
    //     /**
    //      * Custom unserialization
    //      */
    //     public function __unserialize(array $data): void
    //     {
    //         $this->unserializesModels($data);
    //     }
    }

