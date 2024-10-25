<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReviewModel extends Model
{
    use HasFactory;

    protected $table = "product_reviews";

    protected $fillable = ['product_unique', 'product_variant_id', 'name', 'email', 'message', 'rating', 'is_active'];

    public function variant()
    {
        return $this->belongsTo(ProductVariantModel::class, 'product_variant_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_unique', 'product_unique');
    }



    // Register event listeners
    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            $review->updateProductAverageRating();
        });

        static::updated(function ($review) {
            $review->updateProductAverageRating();
        });
    }

    /**
     * Update the average rating for the associated product.
     *
     * @return void
     */
    public function updateProductAverageRating()
    {
        $product = ProductVariantModel::where([
            'product_unique' => $this->product_unique,
            'id' => $this->product_variant_id,
        ])->first();

        if ($product) {
            $averageRating = self::where([
                'product_unique' => $this->product_unique,
                'product_variant_id' => $this->product_variant_id,
                'is_active' => 1,
            ])->avg('rating');

            // Check if the calculated average rating is zero
            $productRating = ($averageRating != 0) ? number_format($averageRating, 1) : 0;

            $product->update(['rating' => $productRating]);
        }
    }
}
