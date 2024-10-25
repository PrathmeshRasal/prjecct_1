<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
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
}
