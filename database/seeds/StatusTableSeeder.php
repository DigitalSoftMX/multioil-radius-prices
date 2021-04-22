<?php

use App\Status;
use Illuminate\Database\Seeder;

class StatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Status::create(['name' => 'depósito']);
        Status::create(['name' => 'disponible']);
        Status::create(['name' => 'compartido']);
        Status::create(['name' => 'Iniciado']);
        Status::create(['name' => 'Pausado']);
        Status::create(['name' => 'Terminado']);
    }
}
