<?php

namespace App\CommonSDK\ElasticSearch\Requests;

use Elastica\Aggregation;
use Elastica\Client;
use Elastica\Query;
use Elastica\Search;
use Elastica\Suggest;

trait PartFilterRequest
{
    public Client $client;

    /**
     */
    public function getParts() {}

    /**
     */
    public function getPartAttributes() {
        return $this->query();
    }

    /**
     */
    protected function query()
    {
        $categoryId = 278;
        $codeString = 'FDC3';
        $codeString = null;

        $search = new Search($this->client);
        $search->addIndex('parts_v2');

        $query = new Query();
        $query->setSource(['id', 'code', 'category_id']);
        $query->setSize(0)->setTrackTotalHits(true);

        // 主查询
        $mainQuery = (new Query\BoolQuery());
        if ($categoryId) $mainQuery->addMust(new Query\Term(['category_id' => $categoryId]));
        if ($codeString) $mainQuery->addMust((new Query\BoolQuery())
            ->addShould(new Query\MatchQuery('code', $codeString))
            ->addShould(new Query\Wildcard('code.keyword', $codeString . '*'))
        );
        $query->setQuery($mainQuery);

        // 聚合 attributes.key 和 attributes.value
        $attributesAggregation = (new Aggregation\Nested('attributes', 'attributes'))
            ->addAggregation(
                (new Aggregation\Terms('keys'))
                    ->setField('attributes.name.keyword')
                    ->setSize(20)
                    ->addAggregation(
                        (new Aggregation\Terms('values'))->setField('attributes.value.keyword')
                    )
            );
        $query->addAggregation($attributesAggregation);


        $requestSet = $search->setQuery($query)->search();

        dd($requestSet->count(), $requestSet->getTotalHits(), $requestSet->getResponse());

        dd(
            $requestSet->getResults(),
            $requestSet->getAggregations()
        );

    }

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
}
