<?php

namespace App\CommonSDK\ElasticSearch\PartFilter;

use App\CommonSDK\ElasticSearch\ElasticSearch;
use Elastica\Aggregation;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\Search;

class Request extends ElasticSearch
{
    protected string $indexName = 'parts_v2';
    protected int | null $categoryId = null;
    protected string | null $codeString = null;
    protected array $attributes = [];

    /**
     * Construct
     *
     * @param $indexName
     * @param null $categoryId
     * @param null $codeString
     * @param array $attributes
     */
    public function __construct($indexName, $categoryId = null, $codeString = null, $attributes = [])
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->codeString = $codeString;
        $this->attributes = $attributes;
        $this->indexName = $indexName;
        $this->search->addIndex($this->indexName);
    }

    /**
     * 获取 parts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getParts()
    {
        $this->query->setTrackTotalHits();
        $this->addMainQuery();
        $this->query->setSource(['id', 'code', 'category_id']);
        $this->query->setSize(20);

        return $this->sendRequestAndTransformResult(__FUNCTION__);
    }

    /**
     * 获取 attributes
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAttributes()
    {
        $this->query->setSize(0);
        $this->query->setTrackTotalHits();
        $this->addMainQuery();
        $this->addAttributesAggregation();

        return $this->sendRequestAndTransformResult(__FUNCTION__);
    }

    /**
     * 发送请求和转变结果
     */
    protected function sendRequestAndTransformResult($type)
    {
        $resultSet = $this->search->setQuery($this->query)->search();

        $data = [
            'parts_num'             =>  $resultSet->getTotalHits(),
        ];

        if ($type === 'getParts') {
            $data['parts']          =   $this->transformParts($resultSet->getResults());
        }

        if ($type === 'getAttributes') {
            $data['attributes']         =   $this->transformAttributes($resultSet->getAggregation('attributes'));
        }

        return $data;
    }

    /**
     * @param $results
     * @return \Illuminate\Support\Collection
     */
    protected function transformParts($results)
    {
        return collect($results)->map(function ($result) {
            return $result->getData();
        });
    }

    /**
     * @param $attributes
     * @return \Illuminate\Support\Collection
     */
    protected function transformAttributes($attributes)
    {
        return collect($attributes['keys']['buckets'])->map(function ($attribute) {
            return [
                'name'      =>  $attribute['key'],
                'values'    =>  collect($attribute['values']['buckets'])->pluck('key'),
            ];
        });
    }

    /**
     * 添加主查询
     */
    protected function addMainQuery()
    {
        $mainQuery = (new Query\BoolQuery());

        // 分类
        if ($this->categoryId) $mainQuery->addMust(new Query\Term(['category_id' => $this->categoryId]));

        // code
        if ($this->codeString) $mainQuery->addMust((new Query\BoolQuery())
            ->addShould(new Query\MatchQuery('code', $this->codeString))
            ->addShould(new Query\Wildcard('code.keyword', $this->codeString . '*'))
        );

        $this->query->setQuery($mainQuery);
    }

    /**
     * 添加 attributes 聚合
     */
    protected function addAttributesAggregation()
    {
        $attributesAggregation = (new Aggregation\Nested('attributes', 'attributes'))
            ->addAggregation(
                (new Aggregation\Terms('keys'))
                    ->setField('attributes.name.keyword')
                    ->setSize(100)
                    ->addAggregation(
                        (new Aggregation\Terms('values'))
                            ->setField('attributes.value.keyword')
                            ->setSize(100)
                    )
            );

        $this->query->addAggregation($attributesAggregation);
    }
}
