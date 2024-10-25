<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ProductVariantModel extends Model
{
    use HasFactory;

    protected $table = "product_variants";

    protected $fillable = ['rating'];

    protected $appends = ['approved_ratings'];

    /**
     * Get all of the images for the ProductVariantModel
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImagesModel::class, 'product_variant_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_unique', 'product_unique');
    }

    public function approvedReviews()
    {
        return $this->hasMany(ProductReviewModel::class, 'product_variant_id', 'id')
            ->where('is_active', 1);
    }

    public function allReviews()
    {
        return $this->hasMany(ProductReviewModel::class, 'product_variant_id', 'id');
    }

    public function getApprovedRatingsAttribute()
    {
        // Get the latest 5 approved reviews as an array
        $latestApprovedReviews = $this->approvedReviews()
            ->latest()
            ->take(5)
            ->get(['id', 'name', 'email', 'message', 'rating', 'created_at'])
            ->toArray();

        // Map the array to change the format of the created_at field
        $latestApprovedReviews = array_map(function ($review) {
            $review['created_at'] = Carbon::parse($review['created_at'])->format('d/m/Y h:iA');
            return $review;
        }, $latestApprovedReviews);

        return $latestApprovedReviews;
    }
}
