<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantImagesModel extends Model
{
    use HasFactory;

    protected $table = "product_variant_images";

    public function variant()
    {
        return $this->belongsTo(ProductVariantModel::class, 'product_variant_id', 'id');
    }
}
