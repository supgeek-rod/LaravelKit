<?php

namespace App\CommonSDK\ElasticSearch;

use App\CommonSDK\ElasticSearch\Requests\PartFilterRequest;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticSearch
{
    use PartFilterRequest;

    public Client $client;

    public static function init()
    {
        $thisInstance = new self();

        $thisInstance->client = ClientBuilder::create()
            ->setBasicAuthentication('elastic', 'pnX8iCS1ckMb6gFSXc1T')
            ->setHosts(['localhost:9200'])
            ->build();

        return $thisInstance;
    }
}
