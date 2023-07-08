<?php

namespace App\CommonSDK\ElasticSearch\PartFilter;

use App\CommonSDK\ElasticSearch\ElasticSearch;
use App\Models\EPartV2\Part;
use Elastica\Aggregation;
use Elastica\Query;
use Illuminate\Support\Str;

class Request extends ElasticSearch
{
    protected string $indexName;
    protected int | null $categoryId = null;
    protected int | null $manufacturerId = null;
    protected string | null $codeString = null;
    protected array | null $attributes = [];

    /**
     * Construct
     *
     * @param $indexName
     * @param null $categoryId
     * @param null $codeString
     * @param array $attributes
     */
    public function __construct($indexName, $categoryId = null, $manufacturerId = null, $codeString = null, $attributes = [])
    {
        parent::__construct();

        $this->categoryId = $categoryId;
        $this->manufacturerId = $manufacturerId;
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
    public function getParts($currentPage = 1, $perPage = 20)
    {
        // $this->query->setSource(['id', 'code', 'category_id']);

        $this->query->setTrackTotalHits();
        $this->addMainQuery();

        // 分页
        $form = ($form = ($currentPage - 1) * $perPage - 1) > 0 ? $form : 0;
        $this->query->setSize($perPage)->setFrom($form);

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
            $data['parts'] = $this->transformParts($resultSet->getResults());
            $data['parts'] = collect($data['parts'])->map(function ($part) {
                $part['avatars'] = Part::makeAvatarAbsolutePath($part['avatars']) ?? null;
                $part['attributes'] = collect($part['attributes'])->pluck('value', 'name');
                $part['manufacturer_name'] = $part['attributes']['Mfr'] ?? null;
                $part['stock_num'] = Part::makeStockNum($part['id']);
                $part['unit_price'] = $part['unit_price'];                              // TODO: 单价需要乘以设定的比率

                unset($part['created_at'], $part['updated_at'], $part['deleted_at']);
                return $part;
            });
        }

        if ($type === 'getAttributes') {
            $data['attributes'] = $this->transformAttributes($resultSet->getAggregation('attributes'));

            // 移除 attributes.Package 中包含 Digi | digi 的值
            $data['attributes'] = $data['attributes']->map(function ($attribute) {
                if ($attribute['name'] === 'Package') {
                    $attribute['values'] = collect($attribute['values'])->filter(function ($value) {
                        return ! Str::contains($value, ['Digi', 'digi']);
                    })->values();
                }

                return $attribute;
            });
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
        if ($this->categoryId) {
            $categoryQuery = new Query\Term(['category_id' => $this->categoryId]);
            $mainQuery->addMust($categoryQuery);
        }

        // 厂商
        if ($this->manufacturerId) {
            $manufacturerQuery = new Query\Term(['manufacturer_id' => $this->manufacturerId]);
            $mainQuery->addMust($manufacturerQuery);
        }

        // code
        if ($this->codeString) {
            $codeQuery = (new Query\BoolQuery())
                ->addShould(new Query\MatchQuery('code', $this->codeString))
                ->addShould((new Query\Wildcard('code.keyword', '*' . $this->codeString . '*'))->setCaseInsensitive(true));

            $mainQuery->addMust($codeQuery);
        }

        // attributes
        if ($this->attributes) {
            foreach ($this->attributes as $attributeName => $attributeValue) {
                $attributeQuery = new Query\BoolQuery();

                $attributeQuery->addMust(new Query\Term(['attributes.name.keyword' => $attributeName]));
                $attributeQuery->addMust(new Query\Terms('attributes.value.keyword', $attributeValue));

                $mainQuery->addMust((new Query\Nested())->setPath('attributes')->setQuery($attributeQuery));
            }
        }

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
