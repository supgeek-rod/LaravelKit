<?php

namespace App\Models\EPartV2;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'mysql_epart_v2';

    /**
     * Scope: 顶级分类
     */
    public function scopeRootCategories()
    {
        return $this->where('parent_id', 0);
    }

    /**
     * Relate: 父分类
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /**
     * Relate: 子分类
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

    /**
     * Relate: 子分类（递归）
     */
    public function recursionChildren()
    {
        return $this->hasMany(self::class, 'parent_id', 'id')
            ->with('recursionChildren')
            ->withCount('parts');
    }

    /**
     * Relate: Part
     */
    public function parts()
    {
        return $this->hasMany(Part::class, 'category_id', 'id');
    }

    /**
     * Attr: 层级分类
     */
    public function getHierarchiesAttribute()
    {
        $layerCategories = [$this];

        while ($category = optional(end($layerCategories))->parent) {
            $layerCategories[] = $category;
        }

        return array_reverse($layerCategories);
    }
}
