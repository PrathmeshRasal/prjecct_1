<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "product";

    protected $fillable = ['product_name', 'product_unique','is_active' /* add other fields */];

    public function products()
    {
        return $this->hasMany(Product::class, 'product_unique', 'product_unique');
    }

    public function productCategories()
    {
        return $this->hasMany(ProductCategoryModel::class, 'product_unique', 'product_unique');
        // Adjust the relationship type and class name as needed
    }

    public function categories()
    {
        return $this->productCategories()
            ->join('categories', 'product_categories.category', '=', 'categories.category_id')
            ->select('categories.*');
    }


}
