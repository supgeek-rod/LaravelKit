<?php

namespace App\Tenements\ePartV2\Models;

use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    protected $connection = 'mysql_epart_v2';

    /**
     * Relate: 厂商
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id', 'id');
    }

    /**
     * Relate: 分类
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    /**
     * Relate: 属性
     */
    public function attributes()
    {
        return $this->hasMany(PartAttribute::class, 'part_id', 'id');
    }
}
