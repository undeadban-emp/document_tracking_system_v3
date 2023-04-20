<?php

namespace Database\Seeders;

use App\Models\TransCode;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TransCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TransCode::create([
            'code' => 'code',
            'value' => 1,
        ]);
    }
}