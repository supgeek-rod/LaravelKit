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
        DB::listen(function ($query) {
            // $this->warn("SQL({$query->time}ms): {$query->sql}");
        });

        $this->line('## Start ' . __METHOD__);

        foreach (($categories = Category::query()->whereHas('parts')->get()) as $index => $category) {
            $this->newLine()->line('#' . $index + 1 . '/' . $categories->count() . "({$category->id}) : Started");
            $startTime = microtime(true);

            try {
                $categoryAttributes = $this->getCategoryAttributes($category);
                $this->saveCategoryAttributes($category, $categoryAttributes);

                $this->line('#' . $index + 1 . '/' . $categories->count() . ' duration: ' . round((microtime(true) - $startTime), 3) . 's');
                $this->line("CategoryID({$category->id}) has " . count($categoryAttributes) . ' attributes');
            } catch (\Exception $exception) {
                $this->error('#' . $index + 1 . '/' . $categories->count() . ' has error');
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
        $partAttributes = $category->attributes()->select('name', 'value')->get();
        unset($category->attributes);

        if ($partAttributes->isNotEmpty()) {
            // 去重
            $partAttributes = $partAttributes->uniqueStrict(function ($item) {
                return $item['name'] . '-' . $item['value'];
            });

            // 二维数组提取成一维数组
            return $partAttributes->reduce(function ($carry, $item) {
                $carry[$item->name][] = $item['value'];

                return $carry;
            }, []);
        }

        return [];
    }
}
