<?php

namespace App\Tenements\ePartV2\Commands;

use App\CommonSDK\ElasticSearch\ElasticSearch;
use App\Tenements\ePartV2\Models\Part;
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

    protected $elasticSearch;

    public function __construct()
    {
        parent::__construct();

        $this->elasticSearch = ElasticSearch::init();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startMicroTime = microtime(true);
        $currentMicroTime = microtime(true);
        $this->line(__METHOD__ . ' Start ...');

        foreach (Part::query()->lazyById(1000, column: 'id') as $part) {
            try {
                $response = $this->elasticSearch->client->index([
                    'id'        =>  $part->id,
                    'index'     =>  'parts',
                    'body'      =>  $this->makePartBody($part),
                ]);

                $durationSeconds = round((float) $currentMicroTime - ($currentMicroTime = microtime(true)), 3);
                $totalSeconds = round((microtime(true) - $startMicroTime), 3);
                $message = $response->asObject()->result;
                $this->line("PartID({$part->id}): {$message}; {$durationSeconds}s / {$totalSeconds}s;");
            } catch (\Exception $exception) {
                $durationSeconds = round((float) $currentMicroTime - ($currentMicroTime = microtime(true)), 3);
                $totalSeconds = round((microtime(true) - $startMicroTime), 3);
                $this->error("PartID({$part->id}): {$durationSeconds}s / {$totalSeconds}s; ({$exception->getMessage()})");
            }
        }
    }

    protected function makePartBody(Part $part)
    {
        $body = $part->toArray();

        $body['attributes'] = $part->attributes->map(function ($item) {
            return [
                'name'      =>  $item->name,
                'value'     =>  $item->value,
            ];
        })->toArray();

        return $body;
    }
}
