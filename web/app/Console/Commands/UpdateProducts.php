<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EcolightUpdateService;

class UpdateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecolight:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Ecolight products';

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
        $updateService = new EcolightUpdateService();
        var_dump($updateService->import());
        return 0;
    }
}
