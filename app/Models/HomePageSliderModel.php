<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePageSliderModel extends Model
{
    use HasFactory;

    protected $table = "homepage_sliders";
    protected $fillable = [
        'image',
        'url',
        'is_active',
    ];
}
