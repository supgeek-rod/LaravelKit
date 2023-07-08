<?php

namespace App\CommonSDK\ElasticSearch;

use Elastica\Aggregation;
use Elastica\Client;
use Elastica\Document;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;

class ElasticSearch
{
    public Client $client;
    public Search $search;
    public Query $query;

    public function __construct()
    {
        $this->client = new Client([
            'host'      =>  'localhost',
            'part'      =>  9200,
            'username'  =>  'elastic',
            'password'  =>  'pnX8iCS1ckMb6gFSXc1T',
        ]);

        $this->search = new Search($this->client);

        $this->query = new Query();
    }

    public static function init()
    {
        return new self();
    }

    public function makeDocument($id, $data)
    {
        return new Document($id, $data);
    }
}
