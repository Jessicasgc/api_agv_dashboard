<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
// use Textalk\Websocket\Client;

class WebSocketController extends Controller
{
    public function sendDataToWebSocket($data)
    {
        try {
            // Membuat koneksi ke WebSocket server
            $client = new \WebSocket\Client('ws://localhost:80/backend');
            
            // Mengirim data sebagai JSON
            $client->send(json_encode($data));
            echo "Send to WebSocket server: " . $data;
            // Menerima dan mencetak balasan dari WebSocket server
            $response = $client->receive();
            echo "Response from WebSocket server: " . $response;
            // // Menutup koneksi
            // $client->close();
        } catch (\Exception $e) {
            \Log::error('Error connecting to WebSocket: ' . $e->getMessage());
        }
    }
}
