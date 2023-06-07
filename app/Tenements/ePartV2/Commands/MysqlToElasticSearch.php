<?php

namespace App\Tenements\ePartV2\Commands;

use Illuminate\Console\Command;

class MysqlToElasticSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ePartV2:mysql-to-elasticsearch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ePartV2 mysql data to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
