<?php

namespace App\Http\Controllers\Api\Slider\Public;

use App\Http\Controllers\Controller;
use App\Models\HomePageSliderModel;
use Exception;
use Illuminate\Http\Request;

require_once app_path('Helpers/Constants.php');
class PublicHomePageSliderController extends Controller
{
    // lsiting

    public function apiGetHomePageSliderPublicList()
    {
        try {
            //code...
            $data = HomePageSliderModel::where('is_active', '=', 1)
                ->latest()
                ->get();

            if (!$data) {
                $response = [
                    'status' => false,
                    'message' => 'Homepage slider list not found',
                    'data' => []
                ];
            } else {
                $data->map(function ($slider) {
                    $slider->image = url(ENV_PUBLIC_PATH . 'homepage_slider/' . $slider->image);
                    return $slider;
                });

                $home = [

                    'slider' => $data
                ];
                $response = [
                    'status' => true,
                    'message' => 'Homepage slider list  found',
                    'data' => ['homepageslider_list' => $data]
                ];
            }
            return response()->json($response, 200);
        } catch (Exception $e) {
            //throw $th;

            $responseArray = [
                "status" => false,
                "message" => "Exception occur",
                "data" => [
                    "error" => $e->getMessage()
                ]
            ];

            return response()->json($responseArray);
        }
    }

}
