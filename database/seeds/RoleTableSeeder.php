<?php

use App\Role;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = new Role();
        $role->name = "admin_master";
        $role->description = "Usuario con nivel de administracion total.";
        $role->save();

        $role = new Role();
        $role->name = 'admin_empresa';
        $role->description = 'Usuario con nivel de administracion media';
        $role->save();

        $role = new Role();
        $role->name = "admin_estacion";
        $role->description = "Usuario con nivel de administracion media.";
        $role->save();

        $role = new Role();
        $role->name = 'despachador';
        $role->description = 'Usuario con nivel de administracion nula';
        $role->save();

        $role = new Role();
        $role->name = "usuario";
        $role->description = "Usuario con nivel de administracion nula.";
        $role->save();
    }
}
