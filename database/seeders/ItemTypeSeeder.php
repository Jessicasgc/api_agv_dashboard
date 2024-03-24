<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ItemType;
use Illuminate\Support\Facades\DB;

class ItemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        
        DB::table('item_types')->insert([
            [
                'id'=> '1',
                'type_code' => 'Type-010101',
                'type_name' => 'Product Type 1'
            ],
            
            [
                'id'=> '2',
                'type_code' => 'Type-020202',
                'type_name' => 'Product Type 2'
            ],


        ]);

    }
}
