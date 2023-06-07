<?php

namespace App\Tenements\ePartV2\Models;

use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    protected $connection = 'mysql_epart_v2';

    public function parts()
    {
        return $this->hasMany(Part::class, 'manufacturer_id', 'id');
    }
}
