<?php

namespace App\Tenements\ePartV2\Commands;

use App\Tenements\ePartV2\Models\Category;
use App\Tenements\ePartV2\Models\CategoryAttribute;
use App\Tenements\ePartV2\Models\Part;
use App\Tenements\ePartV2\Models\PartAttribute;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MakeCategoryAttributesData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ePartV2:make-category-attributes-data
                           {category : 分类 ID，`all` 为全部分类并分发队列}';

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
                $this->warn("SQL({$query->time}ms): {$query->sql}; " . json_encode($query->bindings));
            });
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categoryId = $this->argument('category');
        $categoryQuery = Category::query()->select('id', 'name')->whereHas('parts')->withCount('parts')->orderByDesc('parts_count');

        // 如果 category = all, 则进行队列分发
        if ($categoryId === 'all') {
            foreach ($categoryQuery->get() as $category) {
                Artisan::queue("ePartV2:make-category-attributes-data {$category->id}");
                // $this->executeHandler($category);
            }
        }

        // 如果指定了 category, 则直接执行处理方法
        else {
            $this->executeHandler($categoryQuery->where('id', $categoryId)->first());
        }

        $this->newLine()->line(__METHOD__ . ': done!');
    }

    /**
     * 处理方法
     */
    protected function executeHandler($category)
    {
        $this->newLine()->line('## Start ' . __METHOD__);

        if ($category) {
            $this->line("#{$category->id} ({$category->name}): Start");
            $this->line('Has ' . $category->parts_count . ' parts');

            $startTime = microtime(true);

            try {
                $categoryAttributes = $this->getCategoryAttributes($category);
                $this->saveCategoryAttributes($category, $categoryAttributes);

                $this->line('Has ' . count($categoryAttributes) . ' distinct attributes');
                $this->line('Duration: ' . round((microtime(true) - $startTime), 3) . 's');
            } catch (\Exception $exception) {
                $this->error('Has error');
                $this->error($exception->getMessage());
            }

            $this->line('Usage memory ' . round(memory_get_usage() / 1024 / 1024) . 'MB');
        } else {
            $this->error('Category is not exists');
        }
    }

    /**
     * 获取 CategoryAttributes
     */
    protected function getCategoryAttributes($category)
    {
        $chunkSize = 10000;
        $result = [];

        $startTime = microtime(true);
        $category->attributes()->select('part_attributes.id', 'name', 'value')->chunk($chunkSize, function ($categoryAttributes) use (&$result, &$startTime) {
            $this->line('## Has ' . count($categoryAttributes) . ' attributes');

            // 去重
            $categoryAttributes = $categoryAttributes->uniqueStrict(function ($item) {
                return $item['name'] . '-' . $item['value'];
            });

            // 二维数组提取成一维数组
            $result = $categoryAttributes->reduce(function ($result, $item) {
                $result[$item->name][] = $item['value'];

                return $result;
            }, $result);

            $this->line('-- Duration: ' . round(floatval($startTime) - ($startTime = (microtime(true))), 3) . 's');
            $this->line('-- Usage memory ' . round(memory_get_usage() / 1024 / 1024) . 'MB');
        });

        return $result;
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

        CategoryAttribute::where('category_id', $category->id)->delete();
        return CategoryAttribute::insert($data);
    }
}
