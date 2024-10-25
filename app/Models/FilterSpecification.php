<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FilterSpecification extends Model
{
    use HasFactory;

    public function values()
    {
        return $this->hasMany(FilterSpecificationValue::class)->select(['filter_specification_id', 'value'])->whereNotIn('value', ["Fully Automatic Horizontal Strapping Machine&Fully Automatic Horizontal Strapping Machine"]);
    }
}
