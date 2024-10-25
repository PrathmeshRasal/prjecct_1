<?php

namespace App\Http\Controllers\Api\News\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

require_once app_path('Helpers/Constants.php');
class AdminNewsController extends Controller
{
    // Add
    public function postStoreNews(Request $request)
    {

        try {

            $rules = [

                'header' => 'required|string|max:1000',
                'detail' => 'required|string|max:60000',
                'date' => 'required|date',
                'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB
                // 'author_name' => 'required|string|regex:/^[A-Za-z\s\'-]+$/',

            ];

            $errorMessages = [];

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {

                $responseArray = [
                    "status" => false,
                    "message" => "ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            } else {

                $image = $request->file('thumbnail');

                if ($image) {

                    $folder_path = public_path() . '/news_thumbnails';

                    if (!is_dir($folder_path)) {

                        mkdir($folder_path, 0777, true);

                    }

                    $extension = $image->getClientOriginalExtension();
                    $filename = 'news_thumbnail' . '_' . random_int(10000, 999999) . time() . '.' . $extension;
                    $image->move(public_path('news_thumbnails'), $filename);
                }
 
                $data = [

                    'header' => $request->header,
                    'thumbnail' => $filename,
                    'detail' => $request->detail,
                    'date' => $request->date,
                ];

                $isCreated = NewsModel::create($data);

                if (!$isCreated) {

                    $responseArray = [
                        'status' => false,
                        'message' => 'Unable to create news',
                        'data' => []
                    ];

                    return response()->json($responseArray);

                } else {

                    $responseArray = [
                        'status' => true,
                        'message' => 'News created successfully',
                        'data' => ['news' => $isCreated]
                    ];
                    return response()->json($responseArray);

                }
            }
        } catch (Exception $e) {

            $responseArray = [
                "status" => false,
                "message" => "EXCEPTION_OCCURED",
                "data" => [
                    "error" => [$e->getMessage()]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    // Update

    public function postUpdateNews(Request $request)
    {
        try {

            $rules = [
                'news_id' => 'required|exists:news,id',
                'header' => 'sometimes|string|max:1000',
                'detail' => 'sometimes|string|max:60000',
                'date' => 'sometimes|date',
                'thumbnail' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // 2MB
                // 'author_name' => 'sometimes|string|regex:/^[A-Za-z\s\'-]+$/',
                'is_active' => 'sometimes|bool',

            ];

            $errorMessages = [];

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {

                $responseArray = [
                    "status" => false,
                    "message" => "ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);

            } else {

                $id = $request->news_id;

                $news = NewsModel::where('id', $id)->first();

                if (!$news) {

                    $responseArray = [
                        'status' => false,
                        'message' => 'Unable to find news',
                        'data' => []
                    ];

                    return response()->json($responseArray);
                } else {


                    if ($request->date == null && $request->is_active == null && $request->detail == null && $request->header == null && $request->thumbnail == null) {

                        $responseArray = [
                            "status" => false,
                            "message" => "Prodive at least one field to update",
                            "data" => []
                        ];

                        return response()->json($responseArray);
                    }

                    // $user = Auth::user();
                    // $userId = $user->id;


                    if (isset($request->date)) {

                        $data['date'] = $request->date;
                    }

                    if (isset($request->detail)) {

                        $data['detail'] = $request->detail;
                    }

                    if (isset($request->header)) {

                        $data['header'] = $request->header;
                    }

                    if (isset($request->is_active)) {

                        $data['is_active'] = $request->is_active;
                    }

                    if (!empty($request->file('thumbnail'))) {

                        $filePath = public_path() . '/news_thumbnails/' . $news->thumbnail;

                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }

                        $image = $request->file('thumbnail');

                        $folderPath = public_path() . '/news_thumbnails';

                        if (!is_dir($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }


                        $extension = $image->getClientOriginalExtension();
                        $fileName = 'news_thumbnail' . '_' . random_int(10000, 99999) . '.' . $extension;
                        $image->move(public_path('news_thumbnails'), $fileName);
                        $data['thumbnail'] = $fileName;

                    }

                    $isUpdated = $news->update($data);

                    if (!$isUpdated) {

                        $responseArray = [
                            'status' => false,
                            'message' => 'Unable to update news'
                        ];

                        return response()->json($responseArray);

                    } else {

                        $responseArray = [
                            'status' => true,
                            'message' => 'News updated successfully',
                            'data' => []
                        ];

                        return response()->json($responseArray);

                    }

                }
            }

        } catch (Exception $e) {

            $responseArray = [
                "status" => false,
                "message" => "EXCEPTION_OCCURED",
                "data" => [
                    "error" => [$e->getMessage()]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    // Delete

    public function deleteNews(Request $request)
    {
        try {

            $rules = [

                'news_id' => 'required|exists:news,id',
            ];

            $errorMessages = [];

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {

                $responseArray = [
                    "status" => false,
                    "message" => "ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            } else {
                $id = $request->news_id;

                $news = NewsModel::where('id', $id)->first();

                if (!$news) {

                    $responseArray = [
                        'status' => false,
                        'message' => 'Unable to find news',
                        'data' => []
                    ];

                    return response()->json($responseArray);

                } else {

                    $isDeleted = $news->delete();

                    if (!$isDeleted) {

                        $responseArray = [
                            'status' => false,
                            'message' => 'Unable to delete news',
                            'data' => []
                        ];

                        return response()->json($responseArray);

                    } else {

                        $responseArray = [
                            'status' => true,
                            'message' => 'News deleted successfully',
                            'data' => []
                        ];

                        return response()->json($responseArray);

                    }
                }
            }
        } catch (Exception $e) {

            $responseArray = [
                "status" => false,
                "message" => "EXCEPTION_OCCURED",
                "data" => [
                    "error" => [$e->getMessage()]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    // get single news

    public function getSingleNewsList(Request $request)
    {

        try {
            $rules = [

                'news_id' => 'required|exists:news,id',
            ];

            $errorMessages = [];

            $validator = Validator::make($request->all(), $rules, $errorMessages);

            if ($validator->fails()) {

                $responseArray = [
                    "status" => false,
                    "message" => "ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            } else {

                $id = $request->news_id;
                $news = NewsModel::where('id', $id)->first();

                if (!$news) {

                    $responseArray = [
                        'status' => false,
                        'message' => 'Unable to find news',
                        'data' => []
                    ];

                    return response()->json($responseArray);

                } else {

                    $news->thumbnail = URL(ENV_PUBLIC_PATH . 'news_thumbnails/' . $news->thumbnail);;
                    

                    $news->date = Carbon::parse($news->date)->format("d M Y");


                    $responseArray = [
                        'status' => true,
                        'message' => 'News found successfully',
                        'data' => ["news" => $news]
                    ];

                    return response()->json($responseArray);
                }

            }
        } catch (Exception $e) {

            $responseArray = [
                "status" => false,
                "message" => "EXCEPTION_OCCURED",
                "data" => [
                    "error" => [$e->getMessage()]
                ]
            ];

            return response()->json($responseArray);
        }


    }

    // new list

    public function getPaginatedNewsList(Request $request)
    {
        try {

            $news = NewsModel::latest()->paginate($request->input('per_page'), ['*'], 'page', $request->input('page'));


            if ($news->isEmpty()) {
                $data['news_list'] = [];
                $data['current_page'] = $news->currentPage();
                $data['per_page'] = $news->perPage();
                $data['total'] = $news->total();
                $data['last_page'] = $news->lastpage();

                $responseArray = [
                    'status' => true,
                    'message' => 'Unable to find news list',
                    'data' => $data
                ];

                return response()->json($responseArray);

            } else {

                $news->getCollection()->map(function ($item) {
                    $item->thumbnail = URL(ENV_PUBLIC_PATH . 'news_thumbnails/' . $item->thumbnail);; 

                    $item->date = Carbon::parse($item->date)->format("d M Y");
                    return $item;
                });

                $data['news_list'] = $news->values();
                $data['current_page'] = $news->currentPage();
                $data['per_page'] = $news->perPage();
                $data['total'] = $news->total();
                $data['last_page'] = $news->lastpage();

                $response = [
                    'status' => true,
                    'message' => 'News list found successfully',
                    'data' => $data
                ];
                return response()->json($response);

            }

        } catch (Exception $e) {

            $responseArray = [
                "status" => false,
                "message" => "EXCEPTION_OCCURED",
                "data" => [
                    "error" => [$e->getMessage()]
                ]
            ];

            return response()->json($responseArray);
        }
    }

}
