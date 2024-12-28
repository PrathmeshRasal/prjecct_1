<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Mail\UserActivationMail;
use App\Mail\UserRegistrationMail;
use App\Models\BusinessModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Generator\RandomBytesGenerator;

require_once app_path('Helpers/Constants.php');

class BusinessController extends Controller
{
    //business registration

    function apiBusinessRegistration(Request $request)
    {
        $rules = [
            'name'          => 'required|string|max:100',
            'email'          => 'required|email',
            'mobile'        => 'required|numeric',
            'address'       => 'required|string',
            'state'         => 'required|int',
            'business_type' => 'required|int',
            // 'password'      => 'required'
        ];

        $validator = Validator::make($request->all(), $rules, []);

        if ($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);
        }
        $password = bcrypt($request->mobile);
        $business = new BusinessModel();
        $business->name = $request->name;
        $business->email = $request->email;
        $business->mobile = $request->mobile;
        $business->address = $request->address;
        $business->state = $request->state;
        $business->business_type = $request->business_type;
        $business->password = $password;
        $is_registered = $business->save();

        if ($is_registered) {

            $user = new User();
            $user_unique = rand(100000, 999999);
            $is_exist = User::where('user_unique', $user_unique)->first();
            if($is_exist)
            {
                $user_unique = rand(100000, 999999);
            }
            $user->user_unique = $user_unique;
            $user->first_name = $request->name;
            $user->last_name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->password = $password;
            $user->user_type = 4;
            $user->save();

            $data = [
                'name'    => $request->name,
                'email'   => $request->email,
                'mobile'  => $request->mobile,
                'address' => $request->address,
                'subject' => 'Business Registration mail',
                'message' => 'Thank you for the registration, your details are: ',
            ];

            Mail::to($request->email)->send(new RegistrationMail($data));

            $data['message'] = 'You have new business registratioon with following business details: ';
            $data['name'] = 'Admin';
            Mail::to(ADMIN_MAIL)->send(new RegistrationMail($data));

            $jsonResponse = array(
                'status'    =>  true,
                'message'   =>  "Business registered successfully!",
                'data'      =>  $business
            );

            return response()->json($jsonResponse);
        }
        $jsonResponse = array(
            'status'    =>  false,
            'message'   =>  "Unable to register business!",
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

        if ($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);
        }

        $user = User::where('id', $request->user_id)->first();
        if ($user) {
            $user->is_active = 1;
            $is_activated    = $user->save();

            if ($is_activated) {
                Mail::to($user->email)->send(new UserActivationMail());

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "User activated!",
                    "data"      =>  []
                ];

                return response()->json($responseArray);
            } else {
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
