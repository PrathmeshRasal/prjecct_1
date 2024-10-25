<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariantModel;
use Illuminate\Http\Request;

class PublicCompanyController extends Controller
{
    public function apiGetViewAllCompaniesList(Request $request)
    {
        $company_names = ProductVariantModel::select('company_name')
            ->distinct('company_name')
            ->orderBy('company_name', 'ASC')
            ->get()
            ->pluck('company_name');

        if (empty($company_names)) {

            $responseArray = [
                "status" => false,
                "message" => "Companies not found",
                "data" => ['companies_list' => []],
            ];

            return response()->json($responseArray);
        } else {

            $responseArray = [
                "status" => true,
                "message" => "Companies list found",
                "data" => ['companies_list' => $company_names],
            ];

            return response()->json($responseArray);
        }
    }
}
