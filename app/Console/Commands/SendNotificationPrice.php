<?php

namespace App\Console\Commands;

use App\Repositories\Activities;
use App\User;
use Illuminate\Console\Command;

class SendNotificationPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviando notificaciones de cambios de precios';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $activities = new Activities();
        foreach (User::where('role_id', 3)->get() as $admin) {
            $activities->notificationPricesAndOwners($admin->stationscree);
        }
        return 0;
    }
}
