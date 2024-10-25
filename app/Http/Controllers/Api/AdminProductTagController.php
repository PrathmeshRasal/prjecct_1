<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Models\TagModel;
use App\Rules\ValidatedIsActiveFieldRule;
use App\Rules\ValidateIsActiveFieldIsZeroRule;

require_once app_path('Helpers/Constants.php');

class AdminProductTagController extends Controller
{
    //GET ALL TAG DETAILS
    function apiGetViewAllTags(Request $request){

        $tags = TagModel::select('id', 'title', 'is_active', 'created_at', 'updated_at')
            ->orderBy('id', 'desc')
            ->get();

        $output = [];

        foreach($tags as $tag_data) {

            $output[] = array(
                'id'            =>  $tag_data->id,
                'title'         =>  $tag_data->title,
                'is_active'     =>  $tag_data->is_active,
            );
        }

        if(!empty($output)) {

            $responseArray = [
                "status"    =>  true,
                "message"   =>  "Tag List Found",
                "data"      =>  $output
            ];

            return response()->json($responseArray);

        } else {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                "data"      =>  [
                    "error" =>  ["Tag Not Found"]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    //ADD NEW TAG DETAILS
    function apiPostAddNewTag(Request $request) {

        $rules = [
            'title'     =>  'required|min:3|max:100|unique:tags,title',
            'is_active' =>  ['required', new ValidatedIsActiveFieldRule]
        ];

        $errorMessages = [
            'title.unique'  =>  'Tag name already exists',
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "There is error while filling the form",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $tag = new TagModel;
            $tag->title = $request->title;
            $tag->is_active = $request->is_active;
            $tag->save();

            $responseArray = [
                "status"    =>  true,
                "message"   =>  "Tag added successfully!",
                "data"      =>  [
                    "tag_name"  =>  $tag->title
                ]
            ];

            return response()->json($responseArray);
        }
    }

    //EDIT TAG DETAILS
    function apiPostEditTag(Request $request) {

        $rules = [
            'id'        =>  'numeric|exists:tags,id',
            'title'     =>  'min:3|max:100',
            'is_active' =>  [new ValidatedIsActiveFieldRule]
        ];

        $errorMessages = [
            'title.unique'  =>  'Tag name already exists',
            'id.exists'     =>  'Tag does not exists',
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "There is error while filling the form",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $duplicateTagName = TagModel::where('title', $request->title)
                ->where('id', '!=', $request->id)
                ->first();

            if(!$duplicateTagName) {

                $affectedRows = TagModel::where('id', $request->id)
                    ->update(['title'=>$request->title, 'is_active'=>$request->is_active]);

                if($affectedRows > 0) {

                    $responseArray = [
                        "status"    =>  true,
                        "message"   =>  "Tag updated successfully!",
                        "data"      =>  [
                            "tag_name"  =>  $request->title
                        ]
                    ];

                    return response()->json($responseArray);

                } else {

                    $responseArray = [
                        "status"    =>  false,
                        "message"   =>  ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                        "data"      =>  [
                            "error"  =>  ["Tag does not exists, please try again!"]
                        ]
                    ];

                    return response()->json($responseArray);
                }

            } else {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data"      =>  [
                        "error"  =>  ["Tag name already exists"]
                    ]
                ];

                return response()->json($responseArray);
            }
        }

    }

    //GET SINGLE TAG DETAILS
    function apiGetSingleTagView(Request $request){

        $validator = Validator::make($request->all(), [
            'tag'           =>  'required|exists:tags,id',
        ]);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $tag = TagModel::select('id', 'title', 'is_active')
                ->where('id', $request->tag)
                ->first();

            if($tag) {

                $tagArray = [
                    'title'     =>  $tag->title,
                    'id'        =>  $tag->id,
                    'is_active' =>  $tag->is_active
                ];

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "Request tag found!",
                    "data"      =>  $tagArray
                ];

                return response()->json($responseArray);

            } else {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Request tag not found!",
                    "data"      =>  []
                ];

                return response()->json($responseArray);

            }
        }
    }

    //DELETE TAG DETAILS
    function apiPostSoftDeleteTag(Request $request) {

        $validator = Validator::make($request->all(), [
            'id'        =>  'required|numeric|gt:0',
        ]);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "There was error, while deleting tag",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $affectedRows = TagModel::where('id', $request->id)
                ->delete();

            if($affectedRows) {

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "Tag Delete Successfully",
                    "data"      =>  [
                        "message"  =>  "Tag Delete Successfully"
                    ]
                ];

                return response()->json($responseArray);

            } else {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Unable to delete tag!",
                    "data"      =>  [
                        "error"  =>  "Tag does not exists, please try again!"
                    ]
                ];

                return response()->json($responseArray);
            }
        }
    }
}
