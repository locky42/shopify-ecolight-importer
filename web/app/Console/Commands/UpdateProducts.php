<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EcolightUpdateService;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\RestResourceException;
use Shopify\Exception\RestResourceRequestException;

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
     * @return int
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws RestResourceException
     * @throws RestResourceRequestException
     */
    public function handle(): int
    {
        $updateService = new EcolightUpdateService();
        $updateService->import();

        return 0;
    }
}
