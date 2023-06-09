<?php

namespace App\Tenements\ePartV2\Commands;

use App\Tenements\ePartV2\Models\Category;
use App\Tenements\ePartV2\Models\CategoryAttribute;
use App\Tenements\ePartV2\Models\Part;
use App\Tenements\ePartV2\Models\PartAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeCategoryAttributesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ePartV2:make-category-attributes-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ePartV2 Part* data to Elasticsearch';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('## Start ' . __METHOD__);

        $categoryIds = Category::query()->whereHas('parts')->pluck('id');

        foreach ($categoryIds as $index => $categoryId) {
            $startTime = microtime(true);

            $data = $this->getCategoryAttributes($categoryId);
            $data = collect($data)->map(function ($item, $key) use ($categoryId) {
                return [
                    'category_id'       =>  $categoryId,
                    'name'              =>  $key,
                    'values'            =>  json_encode($item),
                ];
            })->values()->toArray();

            CategoryAttribute::insert($data);

            $this->newLine()->line('#' . $index + 1 . '/' . count($categoryIds) . ' duration: ' . round((microtime(true) - $startTime), 3) . 's');
            $this->line("CategoryID({$categoryId}) has " . count($data) . ' attributes');
        }
    }

    /**
     */
    protected function getCategoryAttributes($categoryId)
    {
        $partIds = Part::query()->where('category_id', $categoryId)->pluck('id');

        if ($partIds) {
            $partAttributes = PartAttribute::query()
                ->select('name', 'value')
                ->whereIn('part_id', $partIds)
                ->get();

            // 去重
            $partAttributes = $partAttributes->uniqueStrict(function ($item) {
                return $item['name'] . '-' . $item['value'];
            });

            // 二维数组提取成一维数组
            return $partAttributes->reduce(function ($carry, $item) use ($categoryId) {
                $carry[$item->name][] = $item['value'];

                return $carry;
            }, []);
        }
    }
}
