<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProductFoundFeedback extends Model
{
    use HasFactory;

    protected $table = "user_product_found_feedbacks";

    protected $fillable = ['product_unique', 'is_found_count', 'is_not_found_count'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_unique', 'product_unique');
    }
}
