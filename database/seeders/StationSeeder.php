<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Station;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $itemTypes = DB::table('item_types')->pluck('id');
        DB::table('stations')->insert([[
            'station_code' => '111',
            'station_name' => '1',
            'id_type' => $itemTypes->random(),
            'x' => '1',
            'y' => '1',
            'stock' => '1',
            'max_capacity' => '1'
        ],
        
        [
            'station_code' => '222',
            'station_name' => '2',
            'id_type' => $itemTypes->random(),
            'x' => '2',
            'y' => '2',
            'stock' => '2',
            'max_capacity' => '2'
        ],
        
        [
            'station_name' => '3',
            'id_type' => $itemTypes->random(),
            'x' => '3',
            'y' => '3',
            'stock' => '3',
            'max_capacity' => '3'
        ],
        
        [
            'station_name' => '4',
            'id_type' => $itemTypes->random(),
            'x' => '4',
            'y' => '4',
            'stock' => '4',
            'max_capacity' => '4'
        ],
        
        [
            'station_name' => '5',
            'id_type' => $itemTypes->random(),
            'x' => '5',
            'y' => '5',
            'stock' => '5',
            'max_capacity' => '5'
        ]
        ]);
    }
}
