<?php

use App\User;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->name = 'Invitado';
        $user->first_surname = 'qwerty';
        $user->second_surname = 'qwerty';
        $user->email = 'admin@material.com';
        $user->password = bcrypt('1234567890Invitado*');
        $user->phone = '2221234567';
        $user->role_id = 1;
        $user->active = '0';
        $user->save();
    }
}
