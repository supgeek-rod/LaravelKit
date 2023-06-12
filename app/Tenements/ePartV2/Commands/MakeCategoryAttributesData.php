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
    protected $signature = 'ePartV2:make-category-attributes-data
                           {category=all : 分类 ID，默认为全部}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ePartV2 Part* data to Elasticsearch';

    public function __construct()
    {
        parent::__construct();

        if (false) {
            DB::listen(function ($query) {
                $this->warn("SQL({$query->time}ms): {$query->sql}");
            });
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('## Start ' . __METHOD__);

        $categoryId = $this->argument('category');
        $categoryQuery = Category::query()->select('id')->whereHas('parts')->orderBy('id');
        if ($categoryId !== 'all') {
            $categoryQuery->where('id', $categoryId);
        }

        foreach (($categories = $categoryQuery->get()) as $index => $category) {
            $this->newLine()->line('#' . $index + 1 . '/' . $categories->count() . "({$category->id}): Start");
            $startTime = microtime(true);

            try {
                $categoryAttributes = $this->getCategoryAttributes($category);
                // $this->saveCategoryAttributes($category, $categoryAttributes);

                $this->line('Duration: ' . round((microtime(true) - $startTime), 3) . 's');
                $this->line('Has ' . count($categoryAttributes) . ' distinct attributes');
            } catch (\Exception $exception) {
                $this->error('Has error');
                // $this->error($exception->getMessage());
            }

            $this->line('Usage memory ' . round(memory_get_usage() / 1024 / 1024) . 'MB');
        }
    }

    /**
     * 保存 CategoryAttributes
     */
    protected function saveCategoryAttributes($category, $data)
    {
        $data = collect($data)->map(function ($item, $key) use ($category) {
            return [
                'category_id'       =>  $category->id,
                'name'              =>  $key,
                'values'            =>  json_encode($item),
            ];
        })->values()->toArray();

        return CategoryAttribute::insert($data);
    }

    /**
     * 获取 CategoryAttributes
     */
    protected function getCategoryAttributes($category)
    {
        $categoryAttributes = $category->attributes()->select('name', 'value')->get();
        $this->line('Has ' . count($categoryAttributes) . ' attributes');
        unset($category->attributes);

        if ($categoryAttributes->isNotEmpty()) {
            // 去重
            $categoryAttributes = $categoryAttributes->uniqueStrict(function ($item) {
                return $item['name'] . '-' . $item['value'];
            });

            // 二维数组提取成一维数组
            return $categoryAttributes->reduce(function ($carry, $item) {
                $carry[$item->name][] = $item['value'];

                return $carry;
            }, []);
        }

        return [];
    }
}
