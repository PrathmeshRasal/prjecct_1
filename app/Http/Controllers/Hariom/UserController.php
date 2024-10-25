<?php

namespace App\Http\Controllers\Hariom;

use App\Http\Controllers\Controller;
use App\Mail\UserActivationMail;
use App\Mail\UserRegistrationMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Generator\RandomBytesGenerator;

require_once app_path('Helpers/Constants.php');

class UserController extends Controller
{
    //user registration
    function apiUserRegistration(Request $request)
    {
        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email',
            'mobile'     => 'required|numeric',
            'address'    => 'required|string',
            'password'   => 'required'
        ];

        $validator = Validator::make($request->all(), $rules, []);

        if($validator->fails())
        {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        }

        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;
        $user->address = $request->address;

        $pass = bcrypt($request->password);

        $user->password = $pass;
        $user->user_unique = rand(100000, 999999);
        $is_registered = $user->save();

        if($is_registered)
        {
            Mail::to($request->email)->send(new UserRegistrationMail());
            Mail::to(ADMIN_MAIL)->send(new UserRegistrationMail());

            $jsonResponse = array(
                'status'    =>  true,
                'message'   =>  "User registerd successfully!",
                'data'      =>  $user
            );

            return response()->json($jsonResponse);
        }
        $jsonResponse = array(
            'status'    =>  false,
            'message'   =>  "Unable to register user!",
            'data'      =>  []
        );

        return response()->json($jsonResponse);
    }

    //User Approval
    function apiUserApproval(Request $request)
    {
        $rules = [
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules, []);

        if($validator->fails())
        {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        }

        $user = User::where('id',$request->user_id)->first();
        if($user)
        {
            $user->is_active = 1;
            $is_activated    = $user->save();

            if($is_activated)
            {
                Mail::to($user->email)->send(new UserActivationMail());

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "User activated!",
                    "data"      =>  []
                ];
    
                return response()->json($responseArray);
            }
            else{
                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Unable to activate user",
                    "data"      =>  []
                ];
    
                return response()->json($responseArray);
            }
        }
        $responseArray = [
            "status"    =>  false,
            "message"   =>  "User not founf",
            "data"      =>  $validator->messages()
        ];

        return response()->json($responseArray);

    }
}
