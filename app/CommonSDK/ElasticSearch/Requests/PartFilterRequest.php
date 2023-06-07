<?php

namespace App\CommonSDK\ElasticSearch\Requests;

use Elastic\Elasticsearch\Client;

trait PartFilterRequest
{
    public Client $client;

    /**
     */
    public function getPartAttributesByCategory()
    {
        $params = [
            'index' => 'parts',
            'body'  => [
                '_source'   =>  ['attrs.name'],
            ],
        ];

        $params = [
            'index' => 'parts',
            'body'  => [
                '_source'   =>  false,
                'aggs'  =>  [
                    'langs' =>  [
                        'terms' =>  [
                            'field' =>  'attrs.name',
                            'size'  =>  500,
                        ],
                    ]
                ],
            ],
        ];

        return $this->client->search($params);
    }

    /**
     */
    public function getParts()
    {
    }
}
