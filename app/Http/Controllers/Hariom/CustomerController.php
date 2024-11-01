<?php

namespace App\Http\Controllers\Hariom;

use App\Http\Controllers\Controller;
use App\Mail\AdminServiceRequestMail;
use App\Mail\CustomerServiceRequestMail;
use App\Mail\UserRegistrationMail;
use App\Models\Hariom\MachineDetail;
use App\Models\Hariom\MachineDetailImage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\AdminServiceResquestMail;
use App\Mail\CustomerServiceResquestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

require_once app_path('Helpers/Constants.php');

class CustomerController extends Controller
{
    //add machine details
    function apiAddMachineDetails(Request $request)
    {
        try {

            $rules = [

                'image.*' => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
                'description' => 'sometimes|string',
                'mach_no' => 'sometimes|string',
                'mach_name' => 'sometimes|string'
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
            }

            $mach_detail = new MachineDetail();
            $mach_detail->description = $request->description;
            $mach_detail->user_id = Auth::user()->id;
            if ($request->mach_no) {
                $mach_detail->mach_no = $request->mach_no;

            }
            if ($request->mach_name) {
                $mach_detail->mach_name = $request->mach_name;

            }
            $mach_detail->save();

            if (isset($request->image)) {
                foreach ($request->image as $img) {
                    $mach_image = $img;
                    $folder_path = public_path() . '/machine_images';
                    if (!is_dir($folder_path)) {
                        mkdir($folder_path, 0777, true);
                    }
                    $extension = $mach_image->getClientOriginalExtension();
                    $filename = 'mach_img' . '-' . random_int(10000, 99999) . '.' . $extension;
                    $mach_image->move(public_path('machine_images'), $filename);

                    $image_data = new MachineDetailImage();
                    $image_data->mach_detail_id = $mach_detail->id;
                    $image_data->image = $filename;
                    $image_data->save();
                }
            }

            Mail::to(Auth::user()->email)->send(new CustomerServiceRequestMail());
            Mail::to(ADMIN_MAIL)->send(new AdminServiceRequestMail());

            $responseArray = [
                "status" => true,
                "message" => "Details added auccessfully",
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

    // update details

    function apiUpdateMachinedetails(Request $request)
    {
        try {
            $rules = [

                'id' => 'required',
                'status' => 'sometimes',
                'is_accept' => 'required',
                'servicer_id' => 'Sometimes|exists:users,id',
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
            }

            $machine = MachineDetail::where('id',$request->id)->first();

            if($machine)
            {
                if($request->status)
                {
                    $machine->status = $request->status;
                }
                if($request->is_accept)
                {
                    $servicer = Auth::user()->id;
                     if($request->servicer_id)
                     {
                        $servicer = $request->servicer_id;
                     }
                     $machine->servicer_id = $servicer;
                     $machine->status = 2; // in progress
                }
                $machine->save();
                $responseArray = [
                    "status" => true,
                    "message" => "Data updated",
                    "data" => []
                ];
    
                return response()->json($responseArray);
            }

            $responseArray = [
                "status" => false,
                "message" => "Machine details not found",
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
