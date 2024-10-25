<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProductFoundFeedback;

class AdminUserProductFoundFeedbackController extends Controller
{
    public function apiGetViewAllUserProductFoundFeedbacksList()
    {
        try {

            $userProductFoundFeedbacksList = UserProductFoundFeedback::latest()->with('product')->paginate(24);

            if ($userProductFoundFeedbacksList->count()) {

                $userFeedbacksList['current_page'] = $userProductFoundFeedbacksList->currentPage();;
                $userFeedbacksList['per_page'] = $userProductFoundFeedbacksList->perPage();
                $userFeedbacksList['total'] = $userProductFoundFeedbacksList->total();
                $userFeedbacksList['last_page'] = $userProductFoundFeedbacksList->lastPage();
                $userFeedbacksList['user_feedbacks_list'] = $userProductFoundFeedbacksList->items();

                $responseArray = [
                    "status" => true,
                    "message" => "List of user feedbacks found.",
                    "data" => $userFeedbacksList,
                ];
            } else {

                $responseArray = [
                    "status" => true,
                    "message" => "User feedbacks not found!",
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
