<?php

namespace App\CommonSDK\ElasticSearch;

use App\CommonSDK\ElasticSearch\Requests\PartFilterRequest;
use Elastica\Client;
use Elastica\Document;

class ElasticSearch
{
    use PartFilterRequest;

    public Client $client;

    public static function init()
    {
        $thisInstance = new self();

        $thisInstance->client = new Client([
            'host'      =>  'localhost',
            'part'      =>  9200,
            'username'  =>  'elastic',
            'password'  =>  'pnX8iCS1ckMb6gFSXc1T',
        ]);

        return $thisInstance;
    }

    public function makeDocument($id, $data)
    {
        return new Document($id, $data);
    }
}
