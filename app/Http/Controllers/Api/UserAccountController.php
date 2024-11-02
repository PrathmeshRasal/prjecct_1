<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Auth;
use Carbon\Carbon;

require_once app_path('Helpers/Constants.php');

class UserAccountController extends Controller
{
    function apiPostAdminLogin(Request $request) {

        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required',
        ]);

        if($validator->fails()) {

            $jsonResponse = array(
                'status'    =>  false,
                'message'   =>  "Unable to perform this action!",
                'data'      =>  $validator->messages()
            );

            return response()->json($jsonResponse);

        } else {

            $authArray = array(
                'email'     =>  $request->email,
                'password'  =>  $request->password,
                'user_type' =>  USER_TYPE_ADMIN
            );

            if(Auth::attempt($authArray)) {

                $authUser = Auth::user();
                if(Auth::user()->user_type!=2)
                {
                    $jsonResponse = array(
                        'status'    =>  false,
                        'message'   =>  "Unable to perform this action!",
                        'data'      =>  [
                            "error" =>  ["Admin details not found, please try again"]
                        ]
                    );
    
                    return response()->json($jsonResponse);
                }

                $jsonResponse = array(
                    'status'    =>  true,
                    'message'   =>  'Login Successful!',
                    'data'      =>  [
                        'token'     =>  $authUser->createToken(env('APP_NAME'))->plainTextToken
                    ]
                );

                return response()->json($jsonResponse);

            } else {

                $jsonResponse = array(
                    'status'    =>  false,
                    'message'   =>  "Unable to perform this action!",
                    'data'      =>  [
                        "error" =>  ["Admin details not found, please try again"]
                    ]
                );

                return response()->json($jsonResponse);
            }
        }
    }

    function apiPostUserLogin(Request $request) {

        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required',
        ]);

        if($validator->fails()) {

            $jsonResponse = array(
                'status'    =>  false,
                'message'   =>  "Unable to perform this action!",
                'data'      =>  $validator->messages()
            );

            return response()->json($jsonResponse);

        } else {

            $authArray = array(
                'email'     =>  $request->email,
                'password'  =>  $request->password,
                'user_type' =>  USER_TYPE_CUSTOMER
            );

            if(Auth::attempt($authArray)) {

                $authUser = Auth::user();
                if(Auth::user()->user_type!=USER_TYPE_CUSTOMER)
                {
                    $jsonResponse = array(
                        'status'    =>  false,
                        'message'   =>  "Unable to perform this action!",
                        'data'      =>  [
                            "error" =>  ["User details not found, please try again"]
                        ]
                    );
    
                    return response()->json($jsonResponse);
                }

                $jsonResponse = array(
                    'status'    =>  true,
                    'message'   =>  'Login Successful!',
                    'data'      =>  [
                        'token'     =>  $authUser->createToken(env('APP_NAME'))->plainTextToken
                    ]
                );

                return response()->json($jsonResponse);

            } else {

                $jsonResponse = array(
                    'status'    =>  false,
                    'message'   =>  "Unable to perform this action!",
                    'data'      =>  [
                        "error" =>  ["User details not found, please try again"]
                    ]
                );

                return response()->json($jsonResponse);
            }
        }
    }

    function apiPostServicerLogin(Request $request) {

        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required',
        ]);

        if($validator->fails()) {

            $jsonResponse = array(
                'status'    =>  false,
                'message'   =>  "Unable to perform this action!",
                'data'      =>  $validator->messages()
            );

            return response()->json($jsonResponse);

        } else {

            $authArray = array(
                'email'     =>  $request->email,
                'password'  =>  $request->password,
                'user_type' =>  3
            );

            if(Auth::attempt($authArray)) {

                $authUser = Auth::user();
                if(Auth::user()->user_type!=3)
                {
                    $jsonResponse = array(
                        'status'    =>  false,
                        'message'   =>  "Unable to perform this action!",
                        'data'      =>  [
                            "error" =>  ["User details not found, please try again"]
                        ]
                    );
    
                    return response()->json($jsonResponse);
                }

                $jsonResponse = array(
                    'status'    =>  true,
                    'message'   =>  'Login Successful!',
                    'data'      =>  [
                        'token'     =>  $authUser->createToken(env('APP_NAME'))->plainTextToken
                    ]
                );

                return response()->json($jsonResponse);

            } else {

                $jsonResponse = array(
                    'status'    =>  false,
                    'message'   =>  "Unable to perform this action!",
                    'data'      =>  [
                        "error" =>  ["User details not found, please try again"]
                    ]
                );

                return response()->json($jsonResponse);
            }
        }
    }

    function apiGetCustomerList()
    {
        try {
            $customers = User::where('user_type',1)->get();
            if(count($customers)>0)
            {
                $responseArray = [
                    "status" => true,
                    "message" => "customers found successfully",
                    "data" => $customers
                ];
    
                return response()->json($responseArray);            }

            $responseArray = [
                "status" => true,
                "message" => "customers not found",
                "data" => []
            ];

            return response()->json($responseArray);

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

    function apiGetServicerList()
    {
        try {
            $servicers = User::where('user_type',3)->get();
            if(count($servicers)>0)
            {
                $responseArray = [
                    "status" => true,
                    "message" => "servicers found successfully",
                    "data" => $servicers
                ];
    
                return response()->json($responseArray);            }

            $responseArray = [
                "status" => true,
                "message" => "servicers not found",
                "data" => []
            ];

            return response()->json($responseArray);

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

    function apiGetUser(Request $request)
    {
        try {

            $rules = [
                'id' => 'required',
            ];
    
            $errorMessages = [];
    
            $validator = Validator::make($request->all(), $rules, $errorMessages);
    
            if ($validator->fails()) {
    
                $responseArray = [
                    "status" => false,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data" => $validator->messages()
                ];
    
                return response()->json($responseArray);
            } 

            $user = User::where('id',$request->id)->first();
            if($user)
            {
                $responseArray = [
                    "status" => true,
                    "message" => "user found successfully",
                    "data" => $user
                ];
    
                return response()->json($responseArray);            }

            $responseArray = [
                "status" => true,
                "message" => "user not found",
                "data" => []
            ];

            return response()->json($responseArray);

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

    function apiPostDeleteUser(Request $request)
    {
        try {

            $rules = [
                'id' => 'required',
            ];
    
            $errorMessages = [];
    
            $validator = Validator::make($request->all(), $rules, $errorMessages);
    
            if ($validator->fails()) {
    
                $responseArray = [
                    "status" => false,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data" => $validator->messages()
                ];
    
                return response()->json($responseArray);
            } 

            $user = User::where('id',$request->id)->delete();
            if($user)
            {
                $responseArray = [
                    "status" => true,
                    "message" => "user deleted successfully",
                    "data" => $user
                ];
    
                return response()->json($responseArray);            }

            $responseArray = [
                "status" => true,
                "message" => "user not deleted",
                "data" => []
            ];

            return response()->json($responseArray);

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
