<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryModel extends Model
{
    use HasFactory;

    protected $table = "product_categories";


    public function products()
    {
        return $this->hasMany(Product::class, 'product_unique', 'product_unique');
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariantModel::class, 'product_unique', 'product_unique');
    }
}
