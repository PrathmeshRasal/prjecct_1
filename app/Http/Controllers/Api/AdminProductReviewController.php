<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReviewModel;
use App\Models\ProductVariantImagesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

require_once app_path('Helpers/Constants.php');

class AdminProductReviewController extends Controller
{
    public function apiGetViewAllProductReviewsList(Request $request)
    {
        $productReviews = ProductReviewModel::paginate(25)->toArray();

        foreach ($productReviews['data'] as &$review) {
            // Check if created_at key exists and is not null
            if (isset($review['created_at']) && !is_null($review['created_at'])) {
                // Convert created_at to a Carbon instance
                $createdAt = Carbon::parse($review['created_at']);

                // Format the date and time as "d/m/y h:ia"
                $formattedDateTime = $createdAt->format('d/m/y h:iA');

                // Remove the original created_at field
                unset($review['created_at']);

                // Add the formatted date and time to the inquiry array with a new key
                $review['created_at'] = $formattedDateTime;
            }

            $name = Product::select('product_name')
                ->where('product_unique', $review['product_unique'])
                ->first();

            $review['product_name'] = $name->product_name ?? 'Deleted Product';

            $image = ProductVariantImagesModel::select('image')
                ->where('product_unique', $review['product_unique'])
                ->orderBy('created_at', 'asc')
                ->first();

            if ($image) {

                $review['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
            } else {

                $review['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
            }
        }

        unset($review);

        if ($productReviews['total'] > 0) {

            $productReviewsList['post_inquiries_list'] = $productReviews['data'];
            $productReviewsList['current_page'] = $productReviews['current_page'];
            $productReviewsList['per_page'] = $productReviews['per_page'];
            $productReviewsList['total'] = $productReviews['total'];
            $productReviewsList['last_page'] = $productReviews['last_page'];

            $responseArray = [
                "status" => true,
                "message" => "Product Reviews List Found",
                "data" => $productReviewsList
            ];

            return response()->json($responseArray);

        } else {

            $responseArray = [
                "status" => false,
                "message" => "Product Reviews Not Found",
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }

    public function apiPostToggleApprovalProductReview(Request $request)
    {
        $rules = [
            'product_review_id' => 'required|integer|exists:product_reviews,id'
        ];

        $errorMessages = [
            'product_review_id.exists' => 'The selected product review id does not exists.'
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $productReview = ProductReviewModel::where('id', $request->product_review_id)->first();

            if ($productReview->is_active) {

                $productReview->is_active = 0;

            } else {

                $productReview->is_active = 1;

            }

            if ($productReview->save()) {
                // Save successful

                $responseArray = [
                    "status" => true,
                    "message" => 'Product review approval toggled successfully',
                    "data" => [
                        [
                            'current_approval_status' => $productReview->is_active ? true : false
                        ]
                    ]
                ];

                return response()->json($responseArray);
            } else {
                // Save failed

                $responseArray = [
                    "status" => false,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data" => ['error' => 'Failed to approve the product review']
                ];

                return response()->json($responseArray);
            }

        }

    }

    public function apiPostDeleteProductReview(Request $request)
    {
        $rules = [
            'product_review_id' => 'required|integer|exists:product_reviews,id'
        ];

        $errorMessages = [
            'product_review_id.exists' => 'The selected product review id does not exists.'
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $productReview = ProductReviewModel::where('id', $request->product_review_id)->first();

            $productReview->delete();

            if (!ProductReviewModel::find($request->product_review_id)) {
                // Deletion successful

                $responseArray = [
                    "status" => false,
                    "message" => 'Review deleted successfully',
                    "data" => []
                ];

                return response()->json($responseArray);
            } else {
                // Deletion failed

                $responseArray = [
                    "status" => false,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data" => ['error' => 'Failed to delete review']
                ];

                return response()->json($responseArray);
            }

        }

    }
}
