<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductReviewModel;
use Illuminate\Http\Request;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategoryModel;
use App\Models\ProductSpecsKeyValueModel;
use App\Models\ProductSpecsModel;
use App\Models\ProductTagModel;
use App\Models\ProductVariantBrochuresModel;
use App\Models\ProductVariantHighlightsModel;
use App\Models\ProductVariantImagesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantSizeModel;
use App\Models\ProductVariantVideosModel;
use App\Models\PublicContactUsModel;
use App\Models\PublicCustomerInquiryModel;
use App\Rules\PositiveNumbersInCsvRule;

use Illuminate\Support\Facades\Validator;
use App\Rules\ValidatedIsActiveFieldRule;
use App\Rules\ValidateIsActiveFieldIsZeroRule;
use Illuminate\Support\Str;

require_once app_path('Helpers/Constants.php');

class PublicProductReviewController extends Controller
{
    function apiPostPublicReviewDetails(Request $request)
    {

        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'name' => 'required|regex:/^[a-zA-Z\s]+$/',
            'email' => 'required|email',
            'message' => 'required|string',
            'product_unique' => 'required|integer|exists:product,product_unique',
            'product_variant_id' => 'required|integer|exists:product_variants,id',
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "There is error while filling the form",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $productReview = new ProductReviewModel;
            $productReview->product_unique = $request->product_unique;
            $productReview->product_variant_id = $request->product_variant_id;
            $productReview->name = Str::Title($request->name);
            $productReview->email = strtolower($request->email);
            $productReview->rating = $request->rating;
            $productReview->message = htmlspecialchars($request->message, ENT_QUOTES, 'UTF-8');
            $productReview->save();

            $responseArray = [
                "status" => true,
                "message" => "Product Review Added Successfully!",
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }
}
