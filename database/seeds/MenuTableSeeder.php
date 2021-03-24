<?php

use App\Menu;
use Illuminate\Database\Seeder;

class MenuTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $menu = Menu::create(['name_modulo' => 'dashboard', 'desplegable' => 0, 'ruta' => 'home', 'id_role' => 0]);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_modulo' => 'Perfil', 'desplegable' => 0, 'ruta' => 'profile', 'id_role' => 0]);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_modulo' => 'Administradores', 'desplegable' => 0, 'ruta' => 'admins', 'id_role' => 0]);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_modulo' => 'Despachadores', 'desplegable' => 0, 'ruta' => 'dispatchers', 'id_role' => 0]);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_modulo' => 'Clientes', 'desplegable' => 0, 'ruta' => 'clients', 'id_role' => 0]);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_modulo' => 'Estaciones', 'desplegable' => 0, 'ruta' => 'stations', 'id_role' => 0]);
        $menu->roles()->sync(1);
    }
}
