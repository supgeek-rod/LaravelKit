<?php

namespace App\Tenements\ePartV2\Commands;

use App\CommonSDK\ElasticSearch\ElasticSearch;
use App\Tenements\ePartV2\Models\Part;
use App\Tenements\ePartV2\Models\PartAttribute;
use Illuminate\Console\Command;

class PartToElasticSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ePartV2:part-to-elasticsearch
                            {indexName : ES 索引名称}
                            {currentChunkSN : 当前块编号}
                            {--perChunkSize=1000000 : 每块的数量}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ePartV2 Part* data to Elasticsearch';

    protected $elasticSearch;

    protected $elasticSearchIndexName;

    protected $chunkSize = 100;

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
        $this->elasticSearchIndexName = $this->argument('indexName');
        $currentChunkSN = $this->argument('currentChunkSN');
        $perChunkSize = $this->option('perChunkSize');

        $partIndex = 0;
        $startMicroTime = microtime(true);
        $currentMicroTime = microtime(true);
        $this->line(__METHOD__ . ' Start ...');

        $beginPartId = $perChunkSize * ($currentChunkSN - 1) + 1;
        $endPartId = $perChunkSize * $currentChunkSN;

        Part::query()->whereBetween('id', [$beginPartId, $endPartId])
            ->chunkById($this->chunkSize, function ($parts) use ($startMicroTime, &$currentMicroTime, &$partIndex) {
            $partAttributes = PartAttribute::whereIn('part_id', $parts->pluck('id')->toArray())->orderBy('id', 'asc')->get();

            $partIndex++;

            foreach ($parts as $part) {
                try {
                    $response = $this->elasticSearch->client->index($params = [
                        'id'        =>  $part->id,
                        'index'     =>  $this->elasticSearchIndexName,
                        'body'      =>  $this->makePartBody($part, $partAttributes),
                    ]);
                } catch (\Exception $exception) {
                    $this->error("#{$partIndex} - ({$part->id}) HasError: {$exception->getMessage()}");
                }
            }

            $durationSeconds = 0 - round(((float) $currentMicroTime) - ($currentMicroTime = microtime(true)), 3);
            $totalSeconds = round((microtime(true) - $startMicroTime), 3);
            $this->line("#{$partIndex} Done; {$durationSeconds}s / {$totalSeconds}s;");
        });
    }

    /**
     * Make part body
     *
     * @param Part $part
     * @param $partAttributes
     * @return array
     */
    protected function makePartBody(Part $part, $partAttributes)
    {
        $body = $part->toArray();

        $body['attributes'] = $partAttributes->where('part_id', $part->id)->pluck('value', 'name')->toArray();

        /*
        $body['attributes'] = $partAttributes->where('part_id', $part->id)->map(function ($item) {
            return [
                'name'      =>  $item->name,
                'value'     =>  $item->value,
            ];
        })->toArray();
        */

        return $body;
    }
}
