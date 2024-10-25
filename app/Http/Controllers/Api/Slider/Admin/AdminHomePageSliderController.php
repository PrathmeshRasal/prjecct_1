<?php

namespace App\Http\Controllers\Api\Slider\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageSliderModel;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

require_once app_path('Helpers/Constants.php');
class AdminHomePageSliderController extends Controller
{
    // Add

    public function apiPostHomePageSliderStore(Request $request)
    {
        try {
            //code...
            $rules = [

                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
                'url' => 'sometimes|string'
            ];

            $errorMessages = array();

            $validator = Validator::make($request->all(), $rules, $errorMessages);
            if ($validator->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'Unable to perform this action',
                    'data' => $validator->messages()
                ];
                return response()->json($response);
            } else {

                $data = [];
                if ($request->url) {
                    $data["url"] = $request->url;
                }

                $homePageImage = $request->file('image');

                if (!empty($homePageImage)) {
                    $folder_path = public_path() . '/homepage_slider';

                    if (!is_dir($folder_path)) {

                        mkdir($folder_path, 0777, true);
                    }
                    $extension = $homePageImage->getClientOriginalExtension();
                    $filename = 'im_homepage' . '-' . random_int(10000, 99999) . '.' . $extension;

                    $homePageImage->move(public_path('homepage_slider'), $filename);
                }

                $data["image"] = $filename;

                $homeSlider = HomePageSliderModel::create($data);
                //dd($homeslider);
                if (!$homeSlider) {
                    $response = [
                        'status' => false,
                        'message' => 'Unable to add homepage slider',
                        'data' => []

                    ];
                } else {
                    $homeSlider->image = url(ENV_PUBLIC_PATH . 'homepage_slider/' . $homeSlider->image);
                    $response = [
                        'status' => true,
                        'message' => 'Homepage slider added successfully',
                        'data' => ['homepage_slider' => $homeSlider]

                    ];
                }
            }

            return response()->json($response);
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

    // Update

    public function apiPostHomePageSliderUpdate(Request $request)
    {
        try {
            //code...
            $rules = [

                'id' => 'required|exists:homepage_sliders,id',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
                'url' => 'sometimes|string',
                'is_active' => 'sometimes|bool'
            ];

            $errorMessages = array();
            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'Unable to perform this action',
                    'data' => $validator->messages()
                ];
                return response()->json($response);
            } else {

                $id = $request->id;

                $homepage = HomePageSliderModel::where('id', $id)->first();
                if (!$homepage) {

                    $response = [
                        "status" => false,
                        "message" => "HomePage slider not found",
                        "data" => []
                    ];

                    return response()->json($response);
                } else {

                    if (!$request->file('image') && !isset($request->is_active) && !isset($request->url)) {
                        $response = [
                            'status' => false,
                            'message' => "Provide at list one field",
                            'data' => []
                        ];
                        return response()->json($response);
                    }
                    //return response()->json($response);

                    $data = [];

                    if ($request->url) {
                        $data["url"] = $request->url;
                    }

                    if (isset($request->is_active)) {
                        $data["is_active"] = $request->is_active;
                    }

                    if (!empty($request->file('image'))) {
                        $filePath = public_path() . '/homepage_slider/' . $homepage->image;

                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        $homeImage = $request->file('image');

                        $folder_path = public_path() . '/homepage_slider';
                        if (!is_dir($folder_path)) {
                            mkdir($folder_path, 0777, true);
                        }
                        $extension = $homeImage->getClientOriginalExtension();
                        $filename = 'im_homepage' . '-' . random_int(10000, 99999) . '.' . $extension;
                        $homeImage->move(public_path('homepage_slider'), $filename);

                        $data["image"] = $filename;
                    }
                    $is_updated = $homepage->update($data);

                    if (!$is_updated) {
                        $response = [
                            'status' => false,
                            'message' => 'Homepage slider not updated',
                            'data' => []

                        ];
                        return response()->json($response);
                    } else {
                        $response = [
                            'status' => true,
                            'message' => 'Homepage slider updated',
                            'data' => []

                        ];
                        return response()->json($response);
                    }
                }

                //return response()->json($response);
            }
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

    // delete

    public function apiPostHomePageSliderDelete(Request $request)
    {
        try {
            //code...
            $rules = [
                'id' => 'required|exists:homepage_sliders,id'

            ];
            $errorMessages = array();

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'Unable to perform this action',
                    'data' => $validator->messages()
                ];

                return response()->json($response);
            } else {

                $id = $request->id;


                $homepageDetail = HomePageSliderModel::where("id", $id)->first();
                // dd($homepageDetail);

                if (!$homepageDetail) {
                    $response = [
                        'status' => false,
                        'message' => 'Homepage slider detail not found',
                        'data' => []

                    ];
                } else {
                    $is_deleted = $homepageDetail->delete();

                    if(!$is_deleted)
                    {

                        $response = [
                            'status' => true,
                            'message' => 'Unable to delete homepage slider',
                            'data' => []

                        ];
                        return response()->json($response);
                    }
                    $response = [
                        'status' => true,
                        'message' => 'Homepage slider deleted successfully',
                        'data' => []

                    ];
                }
                return response()->json($response);
            }
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

    // get sigle slider

    public function apiGetHomePageSliderSpecificId(Request $request)
    {
        try {
            //code...
            $rules = [
                'id' => 'required|exists:homepage_sliders,id'

            ];
            $errorMessages = array();

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {
                $response = [
                    'status' => false,
                    'message' => 'Unable to perform this action',
                    'data' => $validator->messages()
                ];

                return response()->json($response);
            } else {

                $id = $request->id;


                $homepageDetail = HomePageSliderModel::where("id", $id)->first();
                // dd($homepageDetail);

                if (!$homepageDetail) {
                    $response = [
                        'status' => false,
                        'message' => 'Homepage slider detail not found',
                        'data' => []

                    ];
                } else {
                    $homepageDetail->image = url(ENV_PUBLIC_PATH . 'homepage_slider/' . $homepageDetail->image);

                    $response = [
                        'status' => true,
                        'message' => 'Homepage slider detail  found',
                        'data' => ['homepageslider_detail' => $homepageDetail]

                    ];
                }
                return response()->json($response);
            }
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

    // get list

    public function apiGetHomePageSliderPagination(Request $request)
    {
        try {
            //code...

            $data = HomePageSliderModel::latest()
                ->paginate($request->input('per_page'), ['*'], 'page', $request->input('page'));

            // dd($data);
            if ($data->isEmpty()) {

                $homepage['homepageslider_list'] = [];
                $homepage['current_page'] = $data->currentpage();
                $homepage['per_page'] = $data->perpage();
                $homepage['total'] = $data->total();
                $homepage['last_page'] = $data->lastpage();


                $responseArray = [
                    'status' => true,
                    'message' => "HomePage slider list not found",
                    'data' => $homepage
                ];
                return response()->json($responseArray, 200);
            } else {

                $data->map(function ($slider) {
                    $slider->image = url(ENV_PUBLIC_PATH . 'homepage_slider/' . $slider->image);
                    return $slider;
                });

                $home = [

                    'slider' => $data
                ];

                $homepage['homepageslider_list'] = $data->values();
                $homepage['current_page'] = $data->currentpage();
                $homepage['per_page'] = $data->perpage();
                $homepage['total'] = $data->total();
                $homepage['last_page'] = $data->lastpage();

                $responseArray = [
                    'status' => True,
                    'message' => "Homepage slider list found",
                    'data' => $homepage
                ];
            }
            return response()->json($responseArray, 200);
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
