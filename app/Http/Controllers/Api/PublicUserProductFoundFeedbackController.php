<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProductFoundFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicUserProductFoundFeedbackController extends Controller
{
    public function apiPostPublicUserProductFoundFeedbackDetails(Request $request)
    {
        $rules = [
            'is_product_found' => 'required|integer|in:1,0',
            'product_unique' => 'required|integer|exists:product,product_unique',
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

            try {

                // Find or create a record for the specified product
                $userProductFoundFeedback = UserProductFoundFeedback::firstOrNew(['product_unique' => $request->product_unique]);

                $userProductFoundFeedback->save();

                // Increment the corresponding count based on the value of $request->is_product_found
                if ($request->is_product_found) {

                    $userProductFoundFeedback->increment('is_found_count');
                } else {

                    $userProductFoundFeedback->increment('is_not_found_count');
                }

                // Save the changes to the database
                if ($userProductFoundFeedback->save()) {

                    $responseArray = [
                        "status" => true,
                        "message" => "User feedback saved successfully!",
                        "data" => []
                    ];
                } else {

                    $responseArray = [
                        "status" => false,
                        "message" => "Unable to save user feedback!",
                        "data" => []
                    ];
                }

                return response()->json($responseArray);
            } catch (\Exception $e) {

                $responseArray = [
                    "status" => false,
                    "message" => 'Something went wrong!' . ' ' . $e->getMessage(),
                    "data" => []
                ];

                return response()->json($responseArray);
            }
        }
    }
}
