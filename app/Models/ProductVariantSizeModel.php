<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantSizeModel extends Model
{
    use HasFactory;

    protected $table = "product_variant_size";

    public function variant()
    {
        return $this->belongsTo(ProductVariantModel::class, 'product_variant_id', 'id');
    }
}
