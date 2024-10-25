<?php

namespace App\Http\Controllers\Api\News\Public;

use App\Http\Controllers\Controller;
use App\Models\NewsModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

require_once app_path('Helpers/Constants.php');
class PublicNewsController extends Controller
{
    //
    public function getPublicPaginatedNewsList(Request $request)
    {
        try {

            $news = NewsModel::latest()
            ->where('is_active',1)
            ->paginate($request->input('per_page'), ['*'], 'page', $request->input('page'));


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

                    $item->date = Carbon::parse($item->date)->format("M d, Y");
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


    public function getPublicSingleNewsList(Request $request)
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
                    

                    $news->date = Carbon::parse($news->date)->format("M d, Y");


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

}
