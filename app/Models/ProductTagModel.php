<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTagModel extends Model
{
    use HasFactory;

    protected $table = "product_tags";

    function tag() {
        return $this->belongsTo(TagModel::class, 'tag_id', 'id');
    }
}
