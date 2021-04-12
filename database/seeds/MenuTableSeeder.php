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

        $menu = Menu::create(['name_module' => 'dashboard', 'display' => 0, 'route' => '/', 'id_role' => 0, 'icon' => 'nc-bank']);
        $menu->roles()->sync([1, 2, 3]);
        $menu = Menu::create(['name_module' => 'Perfil', 'display' => 0, 'route' => 'profile', 'id_role' => 0, 'icon' => 'nc-single-02']);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_module' => 'Empresas', 'display' => 0, 'route' => 'companies', 'id_role' => 0, 'icon' => 'nc-tile-56']);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_module' => 'Estaciones', 'display' => 0, 'route' => 'stations', 'id_role' => 0, 'icon' => 'nc-bus-front-12']);
        $menu->roles()->sync([1, 2]);
        $menu = Menu::create(['name_module' => 'Administradores', 'display' => 0, 'route' => 'admins', 'id_role' => 0, 'icon' => 'nc-credit-card']);
        $menu->roles()->sync([1, 2]);
        $menu = Menu::create(['name_module' => 'Despachadores', 'display' => 0, 'route' => 'dispatchers', 'id_role' => 0, 'icon' => 'nc-badge']);
        $menu->roles()->sync(1);
        $menu = Menu::create(['name_module' => 'Turnos', 'display' => 0, 'route' => 'schedules', 'id_role' => 0, 'icon' => 'nc-time-alarm']);
        $menu->roles()->sync(3);
        $menu = Menu::create(['name_module' => 'Islas y bombas', 'display' => 0, 'route' => 'islandbomb', 'id_role' => 0, 'icon' => 'nc-bullet-list-67']);
        $menu->roles()->sync(3);
        $menu = Menu::create(['name_module' => 'Clientes', 'display' => 0, 'route' => 'clients', 'id_role' => 0, 'icon' => 'nc-bullet-list-67']);
        $menu->roles()->sync(1);
    }
}
