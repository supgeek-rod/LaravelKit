<?php

namespace App\Tenements\ePartV2\Jobs;

use App\CommonSDK\ElasticSearch\ElasticSearch;
use App\Tenements\ePartV2\Models\Part;
use App\Tenements\ePartV2\Models\PartAttribute;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PartsToElasticSearchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $indexName;
    protected $startId;
    protected $endId;
    protected $chunkSize;

    /**
     * Create a new job instance.
     */
    public function __construct($indexName, $startId, $endId, $chunkSize = 100)
    {
        $this->indexName = $indexName;
        $this->startId = $startId;
        $this->endId = $endId;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Part::query()
            // ->whereBetween('id', [$this->startId, $this->endId])
            ->with('attributes')
            ->chunkById($this->chunkSize, function ($parts) {
                try {
                    if ($parts->count()) $this->batchIndexParts($parts);
                } catch (Exception $exception) {
                    echo('## HasError: ' . $exception->getMessage() . PHP_EOL);
                }

                echo('## Usage memory ' . round(memory_get_usage() / 1024 / 1024) . 'MB' . PHP_EOL);
            });
    }

    /**
     * 批量索引 parts
     */
    protected function batchIndexParts($parts)
    {
        $elasticSearch = ElasticSearch::init();

        $partDocuments = [];

        foreach ($parts as $part) {
            $data = $part->toArray();

            // TODO: unit_price | is_RoHS_cxxx |

            $data['attributes'] = $part->attributes->where('part_id', $part->id)->map(function ($item) {
                return [
                    'name'      =>  $item->name,
                    'value'     =>  $item->value,
                ];
            })->values();

            $partDocuments[] = $elasticSearch->makeDocument($part->id, $data);
        }


        $elasticSearch->client
            ->getIndex($this->indexName)
            ->addDocuments($partDocuments);
    }
}
