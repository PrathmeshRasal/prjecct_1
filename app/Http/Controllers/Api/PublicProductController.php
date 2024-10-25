<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Mail\AdminContactMail;
use App\Mail\AdminInqueryMail;
use App\Mail\PublicContactMail;
use App\Mail\PublicInqueryMail;
use App\Models\Category;
use App\Models\FilterSpecification;
use App\Models\Product;
use App\Models\ProductCategoryModel;
use App\Models\ProductSpecsKeyValueModel;
use App\Models\ProductSpecsModel;
use App\Models\ProductTagModel;
use App\Models\ProductVariantBrochuresModel;
use App\Models\ProductVariantHighlightsModel;
use App\Models\ProductVariantImagesModel;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantSizeModel;
use App\Models\ProductVariantVideosModel;
use App\Models\PublicContactUsModel;
use App\Models\PublicCustomerInquiryModel;
use App\Rules\PositiveNumbersInCsvRule;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidatedIsActiveFieldRule;
use App\Rules\ValidateIsActiveFieldIsZeroRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

require_once app_path('Helpers/Constants.php');

class PublicProductController extends Controller
{
    function apiGetPublicViewCategoriesInParentChild(Request $request)
    {

        $categories = Category::select('category_id', 'category_name', 'category_parent_id', 'image')
            ->orderBy('category_parent_id', 'asc')
            ->get();

        $output = [];

        foreach ($categories as $category) {

            if ($category->category_parent_id == 0) {

                $output[] = array(
                    'category_id' => $category->category_id,
                    'category_name' => $category->category_name,
                    'image' => asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $category->image),
                    'children' => $this->__fetchChildCategory($categories, $category->category_id)
                );
            }
        }

        if (!empty($output)) {

            $responseArray = [
                "status" => true,
                "message" => "Categories list found",
                "data" => $output
            ];

            return response()->json($responseArray);
        } else {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => [
                    "error" => ["Categories not found"]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    function __fetchChildCategory($categories, $parentId)
    {

        $output = [];

        foreach ($categories as $category) {

            if ($category->category_parent_id == $parentId) {

                $output[] = array(
                    'category_id' => $category->category_id,
                    'category_name' => $category->category_name,
                    'category_parent_id' => $category->category_parent_id,
                );
            }
        }

        return $output;
    }

    function apiPublicPostViewThisProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_unique' => 'required|numeric',
            'selected_unit' => 'required|in:inch,mm',
        ]);

        if ($validator->fails()) {
            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            // fetching product

            $product_data = Product::select('id', 'product_name', 'product_unique', 'is_active')
                ->where(['product_unique' => $request->product_unique])
                ->first();

            if (!$product_data) {
                $responseArray = [
                    "status" => false,
                    "message" => "Requested Product Not Found!",
                    "data" => []
                ];

                return response()->json($responseArray);
            } else {

                // fetching product variants

                $product_variant_data = ProductVariantModel::select('*')
                    ->where(['product_unique' => $request->product_unique])
                    ->first();

                $productVariant = [
                    'product_variant_id' => $product_variant_data->id,
                    'product_unique' => $product_variant_data->product_unique,
                    'machine_type' => $product_variant_data->machine_type,
                    'company_name' => $product_variant_data->company_name,
                    'country' => $product_variant_data->country,
                    'product_type' => $product_variant_data->product_type,
                    'product_model' => $product_variant_data->product_model,
                    'industry_name' => $product_variant_data->industry_name,
                    'rating' => $product_variant_data->rating,
                ];

                // fetching product size

                $product_size_data = ProductVariantSizeModel::select('*')
                    ->where(['product_unique' => $request->product_unique])
                    ->first();

                $productVariantSize = [
                    'product_unique' => $product_size_data->product_unique,
                    'product_height_inch' => $product_size_data->product_height_inch,
                    'product_height_mm' => $product_size_data->product_height_mm,
                    'product_width_inch' => $product_size_data->product_width_inch,
                    'product_width_mm' => $product_size_data->product_width_mm,
                    'product_length_inch' => $product_size_data->product_length_inch,
                    'product_length_mm' => $product_size_data->product_length_mm,
                    'product_weight' => $product_size_data->product_weight,
                ];

                // fetching product brochure

                $product_brochure_data = ProductVariantBrochuresModel::select('*')
                    ->where(['product_unique' => $request->product_unique])
                    ->first();

                $productVariantBrochure = "";

                if ($product_brochure_data) {

                    $productVariantBrochure = asset(PRODUCT_BROCHURE_PDFS . '/' . $product_brochure_data->brochure);
                }

                // fetching product intro video

                $product_intro_video_data = ProductVariantVideosModel::select('*')
                    ->where(['product_unique' => $request->product_unique, 'video_type' => 'INTRO'])
                    ->first();

                $productVariantIntroVideo = "";

                if ($product_intro_video_data) {

                    $productVariantIntroVideo = asset(PRODUCT_VIDEOS . '/' . $product_intro_video_data->video);
                }

                // fetching product categories

                $categories = ProductCategoryModel::select('product_unique', 'category')
                    ->where('product_unique', $request->product_unique)
                    ->get();

                $productCategory = [];

                foreach ($categories as $category) {

                    $singleCategory = Category::select('category_id', 'category_name', 'image', 'category_parent_id', 'is_active')
                        ->where('category_id', $category->category)
                        ->first();

                    if ($singleCategory) {

                        $productCategory[] = [
                            'category' => $singleCategory->category_id,
                            //'name'      =>  $singleCategory->category_name,
                            //'image'     =>  asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $singleCategory->image),
                            //'parent'    =>  $singleCategory->category_parent_id,
                            //
                            //'is_active' =>  $singleCategory->is_active,
                        ];
                    }
                }

                // fetching product tags

                $productTags = ProductTagModel::select('tag_id')
                    ->where('product_unique', $request->product_unique)
                    ->get();

                $tags = [];

                foreach ($productTags as $tag) {

                    $tags[] = [
                        'tag_id' => $tag->tag_id
                    ];
                }

                // fetching product specification & values

                $productSpecs = ProductSpecsModel::select(
                    'id',
                    'product_unique',
                    'product_variant_id',
                    'specification_heading',
                    'is_active'
                )->where(['product_unique' => $request->product_unique])
                    ->get();

                $specs = [];

                if ($productSpecs->count() > 0) {

                    foreach ($productSpecs as $spec) {

                        $productSpecsValues = ProductSpecsKeyValueModel::select(
                            'id',
                            'product_unique',
                            'product_variant_id',
                            'product_spec_id',
                            'spec_key',
                            'spec_value',
                            'is_active'
                        )->where(['product_unique' => $spec->product_unique, 'product_spec_id' => $spec->id])
                            ->orderBy('spec_key', 'asc')
                            ->get();

                        $productSpecKeyValue = [];

                        foreach ($productSpecsValues as $productSpecs) {

                            $request->selected_unit == 'mm'
                                ? $searchArray = IN_INCH_CONSTANTS_ARRAY //if mm hide inch values and vice versa.
                                : $searchArray = IN_MM_CONSTANTS_ARRAY; //if inch hide mm values and vice versa.

                            // Flag to indicate if any string is found
                            $stringFound = false;

                            $speckey = "";

                            // Iterate through the array of search strings
                            foreach ($searchArray as $searchString) {
                                // Check if the search string is found within $productSpecs->spec_key
                                if (strpos($productSpecs->spec_key, $searchString) !== false) {

                                    // dd($productSpecs->spec_key);
                                    $speckey = $searchString;
                                    // If found, set the flag and break the loop
                                    $stringFound = true;
                                    break;
                                }
                            }

                            // Check the value of $stringFound is false to determine if any string was not found and it's filtered
                            if (!$stringFound) {

                                $productSpecKeyValue[] = array(
                                    'id' => $productSpecs->id,
                                    //'product'    =>     $productSpecs->product_unique,
                                    //'variant'    =>     $productSpecs->product_variant_id,
                                    'spec' => $productSpecs->product_spec_id,
                                    'spec_key' => $productSpecs->spec_key,
                                    'spec_value' => $productSpecs->spec_value,
                                    'is_active' => $productSpecs->is_active,
                                );

                            }
                            else{

                                if (strpos($productSpecs->spec_key, $speckey) == false)
                                {
                                    $productSpecKeyValue[] = array(
                                        'id' => $productSpecs->id,
                                        //'product'    =>     $productSpecs->product_unique,
                                        //'variant'    =>     $productSpecs->product_variant_id,
                                        'spec' => $productSpecs->product_spec_id,
                                        'spec_key' => $productSpecs->spec_key,
                                        'spec_value' => $productSpecs->spec_value,
                                        'is_active' => $productSpecs->is_active,
                                    );
                                }

                            }

                        }

                        $specs[] = array(
                            'id' => $spec->id,
                            'product' => $spec->product_unique,
                            'variant' => $spec->product_variant_id,
                            'is_active' => $spec->is_active,
                            'specification_heading' => $spec->specification_heading,
                            'specifications' => $productSpecKeyValue
                        );
                    }
                }

                // fetching product Highlights

                $productHighlights = ProductVariantHighlightsModel::select(
                    'id',
                    'product_unique',
                    'product_variant_id',
                    'highlight_text'
                )
                    ->where(['product_unique' => $request->product_unique])
                    ->orderBy('id', 'desc')
                    ->get();

                $highlights = [];

                foreach ($productHighlights as $high) {

                    $highlights[] = array(
                        'id' => $high->id,
                        'product' => $high->product_unique,
                        'variant' => $high->product_variant_id,
                        'highlight_text' => $high->highlight_text,
                    );
                }

                // fetching product images

                $productImages = ProductVariantImagesModel::select(
                    'id',
                    'product_unique',
                    'product_variant_id',
                    'image'
                )
                    ->where(['product_unique' => $request->product_unique])
                    ->orderBy('id', 'desc')
                    ->get();

                $imageoutput = [];

                foreach ($productImages as $image) {

                    $imageoutput[] = array(
                        'id' => $image->id,
                        //'product'  =>  $image->product_unique,
                        'variant' => $image->product_variant_id,
                        'file_name' => $image->image,
                        'image' => asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image),
                    );
                }

                $product = array(
                    'product' => array(
                        'product_name' => $product_data->product_name,
                        'is_active' => $product_data->is_active,
                        'categories' => $productCategory,
                        'productvariant' => $productVariant,
                        'productsize' => $productVariantSize,
                    ),
                    'product_tags' => $tags,
                    'product_specification' => $specs,
                    'product_highlights' => $highlights,
                    'product_image' => $imageoutput,
                    'approved_ratings' => $product_variant_data->approved_ratings,
                    'productbrochure' => $productVariantBrochure,
                    'productintrovideo' => $productVariantIntroVideo,
                );

                $responseArray = [
                    "status" => true,
                    "message" => "Request product found!",
                    "data" => $product
                ];

                return response()->json($responseArray);
            }
        }
    }

    function apiPostPublicViewProductListDetails(Request $request)
    {

        $rules = [
            'category_id' => 'required|json',
            'sort_by' => 'required|in:sort_by_popularity,sort_by_average_rating,sort_by_latest,sort_by_a_to_z,sort_by_z_to_a',
            'company_name' => 'sometimes|json',
            'machine_type' => 'sometimes|json',
            'strap_type' => 'sometimes|json',
            'strap_width_range' => 'sometimes|json',
            'strap_cycles_per_minute' => 'sometimes|json',
            'table_surface' => 'sometimes|json',
            'tape_type' => 'sometimes|json',
            'tape_head' => 'sometimes|json',
            'band_type' => 'sometimes|json',
            'band_width' => 'sometimes|json',
            'belt' => 'sometimes|json',
            'selected_unit' => 'required|in:inch,mm',
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
        } else {

            $productQuery = Product::select(
                'product.product_name',
                'product.product_unique',
                'product.is_active'
            )->where('product.is_active', IS_ACTIVE_YES);


            // if product categories/filters are passed

            $category_id = $this->__validateJsonArrayInput($request->category_id, 'category id', 'is_integer');
            $this->__validateJsonArrayInput($request->machine_type, 'machine types');
            $this->__validateJsonArrayInput($request->strap_type, 'strap types');
            $this->__validateJsonArrayInput($request->strap_width_range, 'strap width ranges');
            $this->__validateJsonArrayInput($request->strap_cycles_per_minute, 'strap cycles per minute');
            $this->__validateJsonArrayInput($request->table_surface, 'table surfaces');

            if (isset($request->category_id) && !empty(json_decode($request->category_id))) {

                // FINDING PARENT CATEGORY_ID

                $category = Category::where('category_id', $category_id[0])->first();

                if (!$category) {
                    $responseArray = [
                        "status" => false,
                        "message" => 'category id dosen\'nt exists',
                        "data" => []
                    ];

                    return response()->json($responseArray);
                }

                $url_category_name = $category->category_name;

                $parent_category_id = $category->category_id;

                if ($category->category_parent_id != 0) {
                    $parent_category_id = $category->category_parent_id;
                    $category = Category::where('category_id', $parent_category_id)->first();
                }

                $filterSpecifications = FilterSpecification::select('id', 'category_id', 'request_key', 'specification_heading')->where('category_id', $parent_category_id)->with('values')->get();
                // dd($filterSpecifications->toArray());

                $categories = Category::select('category_id', 'category_name')
                    ->where('category_parent_id', $parent_category_id)
                    ->get();

                $category_ids = $categories->pluck('category_id')->toArray();

                // FINDING FILTERS_FOR_SIDEBAR FOR CURRENT PARENT CATEGORY_ID

                // $productVariants = ProductVariantModel::select('product_variants.company_name', 'product_variants.machine_type', 'product_specifications_value.spec_key', 'product_specifications_value.spec_value')
                //     ->join('product_categories', 'product_variants.product_unique', '=', 'product_categories.product_unique')
                //     ->join('product_specifications_value', 'product_variants.product_unique', '=', 'product_specifications_value.product_unique')
                //     ->whereIn('product_categories.category', $category_ids)
                //     ->distinct('product_variants.product_unique')
                //     ->get();

                // $result = [];

                // foreach ($productVariants as $item) {
                //     $specKey = $item['spec_key'];
                //     $specValue = $item['spec_value'];

                //     if (!isset($result[$specKey])) {
                //         $result[$specKey] = [];
                //     }

                //     $result[$specKey][] = $specValue;
                // }

                // // Apply array_unique to each subarray
                // $result = array_map('array_unique', $result);

                // // Convert all subarrays to indexed arrays
                // $result = array_map('array_values', $result);

                // $filters = [
                //     'Strap Type',
                //     'Strap Width Range',
                //     'Strap Cycles per Minute',
                //     'Table Surface',
                //     'Tape Type',
                //     'Tape head',
                //     'Band Type',
                //     'Belt',
                // ];

                // $result = array_intersect_key($result, array_flip($filters));

                // $filters_for_sidebar = [];

                // foreach ($result as $key => $values) {

                //     switch ($key) {
                //         case 'Strap Type':
                //             $request_key = 'strap_type';
                //             break;

                //         case 'Strap Width Range':
                //             $request_key = 'strap_width_range';
                //             break;

                //         case 'Strap Cycles per Minute':
                //             $request_key = 'strap_cycles_per_minute';
                //             break;

                //         case 'Tape Type':
                //             $request_key = 'tape_type';
                //             break;

                //         case 'Tape head':
                //             $request_key = 'tape_head';
                //             break;

                //         case 'Band Type':
                //             $request_key = 'band_type';
                //             break;

                //         case 'Band Width':
                //             $request_key = 'band_width';
                //             break;

                //         case 'Belt':
                //             $request_key = 'belt';
                //             break;

                //         default:
                //             $request_key = 'table_surface';
                //             break;
                //     }

                //     $filters_for_sidebar[] = [
                //         'key' => $request_key,
                //         'name' => $key,
                //         'values' => $values,
                //     ];
                // }

                // $company_names = $productVariants->pluck('company_name')->unique()->toArray();
                // $machine_types = $productVariants->pluck('machine_type')->unique()->toArray();

                // $filters_for_sidebar[] = [
                //     'key' => 'company_name',
                //     'name' => 'Company Names',
                //     'values' => array_values($company_names)
                // ];
                // $filters_for_sidebar[] = [
                //     'key' => 'machine_type',
                //     'name' => 'Machine Types',
                //     'values' => array_values($machine_types)
                // ];

                // PRODUCT LISTING CODE BLOCK STARTS HERE


                $productQuery = Product::select(
                    'product.product_name',
                    'product.product_unique',
                    'product.is_active'
                )->where('product.is_active', IS_ACTIVE_YES);

                $productQuery->join('product_categories', 'product.product_unique', '=', 'product_categories.product_unique')
                    ->whereIn('product_categories.category', $category_ids);

                $columnMapping = [
                    'company_name',
                    'machine_type',
                ];

                $table = 'product_variants';

                $joinedProductVariantsTable = false;

                foreach ($columnMapping as $filter) {

                    if (isset($request->$filter)) {


                        $jsonfilter = json_decode($request->$filter);

                        foreach($jsonfilter as $key => $requestFilter) {
                            if ($requestFilter == "Fully Automatic Horizontal Strapping Machine" || $requestFilter == "Fully Automatic Online") {

                                $jsonfilter[$key] = "Fully Automatic";

                            }
                        }
                        if (!$joinedProductVariantsTable) {
                            $productQuery = $productQuery->join($table, 'product.product_unique', '=', $table . '.product_unique');
                            $joinedProductVariantsTable = true;
                        }


                        $productQuery = $productQuery->whereIn($table . '.' . $filter, $jsonfilter);

                    }
                }

                // $filterTableMapping = [
                //     'strap_type' => 'Strap Type',
                //     'strap_width_range' => 'Strap Width Range',
                //     'strap_cycles_per_minute' => 'Strap Cycles per Minute',
                //     'table_surface' => 'Table Surface',
                //     'tape_type' => 'Tape Type',
                //     'tape_head' => 'Tape head',
                //     'band_type' => 'Band Type',
                //     'band_width' => 'Band Width',
                //     'belt' => 'Belt',
                // ];

                $table = 'product_specifications_value';

                $joinedTable = false;

                $keys_column = 'spec_key';
                $values_column = 'spec_value';

                $searchThroughSpecifications = FilterSpecification::select('id', 'category_id', 'request_key', 'specification_heading')
                    ->where('category_id', $parent_category_id)
                    ->whereNotIn('request_key', $columnMapping)
                    ->with('values')
                    ->get();

                // dd($searchThroughSpecifications[0]->toArray());

                foreach ($searchThroughSpecifications as $filterSpecification) {

                    $filter = $filterSpecification->request_key;
                    $key = $filterSpecification->specification_heading;

                    // dd($filterSpecification->specification_heading);
                    // echo $filterSpecification->specification_heading . " ";

                    if (
                        isset($request->$filter)
                        && !empty($requestedFilterArray = json_decode($request->$filter))
                    ) {
                        if (!$joinedTable) {
                            $productQuery = $productQuery->join($table, 'product.product_unique', '=', $table . '.product_unique');
                            $joinedTable = true;
                        }

                        // $productQuery->where($table . '.' . $keys_column, $key);

                        if ($filter == 'strap_width_range' || $filter == 'tape_head' || $filter == 'band_width') {

                            $productQuery = $productQuery->where(function ($productQuery) use ($filter, $requestedFilterArray, $table, $values_column, $keys_column, $key, $request) {

                                // dd(
                                //     '$filter=' . $filter,
                                //     '$requestedFilterArray=' . request()->$filter,
                                //     '$table=' . $table,
                                //     '$values_column=' . $values_column,
                                //     '$keys_column=' . $keys_column,
                                //     '$key=' . $key,
                                // );

                                $productQuery->where(function ($query) use ($requestedFilterArray, $table, $keys_column, $values_column, $request, $filter) {
                                    foreach ($requestedFilterArray as $value) {
                                        // Process the value as needed (convert to numeric, etc.)
                                        $numericValue = preg_replace('/[^0-9.]/', '', $value);
                                        $floatValue = (float) $numericValue;

                                        if ($floatValue == (int) $floatValue) {
                                            $value = (int) $floatValue;
                                        } else {
                                            $value = $floatValue;
                                        }

                                        if ($filter == 'tape_head') {
                                            $key = 'Tape Width';
                                        } elseif ($filter == 'band_width') {
                                            $key = 'Band Width';
                                        }

                                        // Ensure proper logical operators with OR
                                        $query
                                            ->orWhereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                            ->orWhereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key . ' in ' . $request->selected_unit])
                                            ->orWhere("{$table}.{$values_column}", 'LIKE', "%{$value}%");
                                    }
                                });

                                $data = [];
                                foreach ($requestedFilterArray as $value) {

                                    // if ($filter == 'strap_width_range') {

                                    //     $before_first_mm = explode("mm", $value)[0]; //"5.55mm & 3/16"" trimmed to "5.55"

                                    //     $value = $before_first_mm;
                                    // }

                                    $numericValue = preg_replace('/[^0-9.]/', '', $value);

                                    // Convert the numeric part to a float
                                    $floatValue = (float) $numericValue;

                                    // Check if the float value has a decimal part of .0
                                    if ($floatValue == (int) $floatValue) {

                                        // If it has a decimal part of .0, convert to integer
                                        $value = (int) $floatValue;
                                    } else {

                                        // If not, keep it as a float
                                        $value = $floatValue;
                                    }

                                    if ($filter == 'tape_head') {

                                        $key = 'Tape Width';
                                    } elseif ($filter == 'band_width') {

                                        $key = 'Band Width';
                                    }

                                    // $productQuery
                                    //     // ->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                    //     // ->orWhereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key . ' in ' . $request->selected_unit]);
                                    //     ->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key . ' in ' . $request->selected_unit]);

                                    // // $productQuery->WhereRaw("FIND_IN_SET(?, $table.$values_column)", [$value]);
                                    // $productQuery->where("{$table}.{$values_column}", 'LIKE', "%{$value}%");
                                    $data[] = $value;
                                    $productQuery
                                        ->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                        ->orWhereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key . ' in ' . $request->selected_unit]);

                                    // $productQuery->where("{$table}.{$values_column}", 'LIKE', "%{$value}%");

                                }

                                $productQuery->whereIn("{$table}.{$values_column}", $data);
                                // if ($request->selected_unit == "mm") {
                                //     $productQuery->whereIn("{$table}.{$values_column}", $data);

                                // } elseif ($request->selected_unit == "inch") {
                                    
                                //     foreach ($data as $value) {
                                //         $productQuery->whereIn("{$table}.{$values_column}", $data);
                                //         // $productQuery->where("{$table}.{$values_column}", 'LIKE', "%{$value}%");
                                //     }
                                // }

                            });
                        } elseif ($filter == 'strap_per_minute') {

                            $productQuery = $productQuery->where(function ($productQuery) use ($requestedFilterArray, $table, $values_column, $keys_column, $key) {

                                foreach ($requestedFilterArray as $value) {

                                    if ($value != 'Manual') {

                                        if ($value != '50+') {

                                            $values = explode('-', $value);

                                            $min = $values[0];

                                            $max = $values[1] ?? null;

                                            if ($max) {

                                                $productQuery->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                                    ->whereRaw("CAST($table.$values_column AS SIGNED) BETWEEN ? AND ?", [$min, $max]);
                                            } else {

                                                $productQuery->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                                    ->whereRaw("FIND_IN_SET(?, $table.$values_column) > 0", [$min]);
                                            }
                                        } else {

                                            $productQuery->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                                ->whereRaw("CAST($table.$values_column AS SIGNED) > 50");
                                        }
                                    } else {

                                        $productQuery
                                            ->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                            ->WhereRaw("FIND_IN_SET(?, $table.$values_column)", [$value]);
                                    }
                                }
                            });
                        } else {
                            if ($key == "Tape Type") {
                                $key = "Material";
                            }

                            if ($key == "Band Type") {
                                $key = "Applicable Material";
                            }

                            foreach ($requestedFilterArray as $id => $requestedFilter) {

                                if ($requestedFilter == "PP band") {

                                    $requestedFilterArray[$id] = "OPP film,PP band";
                                }
                            }
                            // dd($requestedFilterArray);
                            $productQuery = $productQuery
                                ->whereRaw("LOWER({$table}.{$keys_column}) = LOWER(?)", [$key])
                                ->whereIn($table . '.' . $values_column, $requestedFilterArray);
                        }
                    }
                }
            }
            // dd($productQuery);
            $sql = $productQuery->toSql();
            $bindings = $productQuery->getBindings();

            // Combine the SQL and bindings
            $fullQuery = vsprintf(str_replace('?', '%s', $sql), $bindings);

            // dd($fullQuery);

            switch ($request->sort_by) {

                case 'sort_by_latest':

                    $productQuery = $productQuery->orderby('product.created_at', 'desc');
                    break;

                case 'sort_by_z_to_a':

                    $productQuery = $productQuery->orderby('product.product_name', 'desc');
                    break;

                case 'sort_by_average_rating':

                    if (!$joinedProductVariantsTable) {
                        $productQuery = $productQuery->join('product_variants', 'product.product_unique', '=', 'product_variants.product_unique');
                        $joinedProductVariantsTable = true;
                    }

                    $productQuery = $productQuery->orderby('product_variants.rating', 'desc');
                    break;

                case 'sort_by_popularity':
                    $productQuery = $productQuery
                        ->join('product_tags', 'product.product_unique', '=', 'product_tags.product_unique')
                        ->orderByRaw('CASE WHEN product_tags.tag_id = 2 THEN 0 ELSE 1 END');
                    // ->orderby('product.product_name', 'asc');
                    // ->where('product_tags.tag_id', 2)
                    break;

                default:
                    $productQuery = $productQuery->orderby('product.product_name', 'asc');
                    break;
            }

            $products = $productQuery->distinct('product.product_unique')->paginate(24)->toArray();

            foreach ($products['data'] as &$product) {

                $image = ProductVariantImagesModel::select('image')
                    ->where('product_unique', $product['product_unique'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($image) {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
                } else {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
                }

                // product variants

                $variant = ProductVariantSizeModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['product_height_inch'] = $variant->product_height_inch;
                $product['product_height_mm'] = $variant->product_height_mm;
                $product['product_width_inch'] = $variant->product_width_inch;
                $product['product_width_mm'] = $variant->product_width_mm;
                $product['product_length_inch'] = $variant->product_length_inch;
                $product['product_length_mm'] = $variant->product_length_mm;
                $product['product_weight'] = $variant->product_weight;

                $variant = ProductVariantModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['rating'] = $variant->rating;
            }

            if ($category->category_name == $url_category_name) {

                $url_category_name = null;
            }

            if ($products['total'] > 0) {

                $productsList['current_page'] = $products['current_page'];
                $productsList['per_page'] = $products['per_page'];
                $productsList['total'] = $products['total'];
                $productsList['last_page'] = $products['last_page'];
                $productsList['products_list'] = $products['data'];
                $responseArray = [
                    "status" => true,
                    "message" => "List of Products found",
                    "main_category_name" => $category->category_name,
                    "url_category_name" => $url_category_name,
                    "data" => $productsList,
                    "filters_for_sidebar" => $filterSpecifications->toArray()
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => true,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "main_category_name" => $category->category_name,
                    "url_category_name" => $url_category_name,
                    "data" => ['products_list' => []],
                    "filters_for_sidebar" => $filterSpecifications->toArray()
                ];

                return response()->json($responseArray);
            }
        }
    }

    function apiGetPublicViewPopularProductsList(Request $request)
    {
        $categories = [
            1 => 'strapping_machines',
            2 => 'tape_sealing',
            8 => 'stretch_wrapping_machines',
        ];

        foreach ($categories as $category_id => $category) {

            $productQuery = Product::select(
                'product.product_name',
                'product.product_unique',
                'product.is_active'
            )->where('product.is_active', IS_ACTIVE_YES);

            $productQuery->join('product_categories', 'product.product_unique', '=', 'product_categories.product_unique')
                ->join('product_tags', 'product.product_unique', '=', 'product_tags.product_unique')
                ->where('product_tags.tag_id', 2)
                ->where('product_categories.category', $category_id);

            $products = $productQuery->latest('product.created_at')
                ->distinct('product_tags.product_unique')
                ->take(20)
                ->get()
                ->toArray();

            foreach ($products as &$product) {

                $image = ProductVariantImagesModel::select('image')
                    ->where('product_unique', $product['product_unique'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($image) {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
                } else {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
                }

                // product variants

                $variant = ProductVariantSizeModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['product_height_inch'] = $variant->product_height_inch;
                $product['product_height_mm'] = $variant->product_height_mm;
                $product['product_width_inch'] = $variant->product_width_inch;
                $product['product_width_mm'] = $variant->product_width_mm;
                $product['product_length_inch'] = $variant->product_length_inch;
                $product['product_length_mm'] = $variant->product_length_mm;
                $product['product_weight'] = $variant->product_weight;

                $variant = ProductVariantModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['rating'] = $variant->rating;
            }

            if (count($products) > 0) {
                $productsList[$category] = $products;
            } else {
                $productsList[$category] = [];
            }
        }

        if (!empty($productsList) && count($productsList) > 0) {

            $responseArray = [
                "status" => true,
                "message" => "List of Products found",
                "data" => $productsList,
            ];

            return response()->json($responseArray);
        } else {

            $responseArray = [
                "status" => false,
                "message" => 'List of Products not found',
                "data" => [],
            ];

            return response()->json($responseArray);
        }
    }

    function apiPostPublicContactUsDetails(Request $request)
    {

        $rules = [
            'name' => 'required',
            'email_id' => 'email|required',
            'mobile_no' => 'required|size:10',
            'message' => 'required',
            'file' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:5120'
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "There is error while filling the form",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $contact = new PublicContactUsModel;
            $contact->name = $request->name;
            $contact->email_id = $request->email_id;
            $contact->mobile_no = $request->mobile_no;
            $contact->message = $request->message;
            
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filePath = $file->store('/public/uploads');
                $contact->file_url = env("APP_URL") . "/storage/app/" . $filePath;
            } else {
                $contact->file_url = null;
            }
            $contact->save();


            // Mail trigger

            $subject = 'New Contact Us Details';
            $greeting = "Hello Admin!";
            $content = 'Success!';
            $data = [
                "name" => $request->name,
                "email_id" => $request->email_id,
                "mobile" => $request->mobile_no,
                "meaasge" => $request->message,
                "file_url" => $contact->file_url
            ];

            $email = ADMIN_MAIL;
            Mail::to($email)->send(new AdminContactMail($subject, $greeting, $content, $data));

            Mail::to($request->email_id)->send(new PublicContactMail($subject, $greeting, $content, $data));

            $responseArray = [
                "status" => true,
                "message" => "Contact Details Added Successfully!",
                "file_url" => $contact->file_url,
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }

    function apiGetPublicContactUsDetails(Request $request)
    {

        $contact = PublicContactUsModel::select('id', 'name', 'email_id', 'mobile_no', 'message', 'file_url')
            ->paginate($request->input('per_page'), ['*'], 'page', $request->input('page'))//paginate(50)
            ->toArray();

        if ($contact['total'] > 0) {

            $contactList['products_list'] = $contact['data'];
            $contactList['current_page'] = $contact['current_page'];
            $contactList['per_page'] = $contact['per_page'];
            $contactList['total'] = $contact['total'];
            $contactList['last_page'] = $contact['last_page'];

            $responseArray = [
                "status" => true,
                "message" => "Contact Us List Found",
                "data" => $contactList
            ];

            return response()->json($responseArray);
        } else {

            $responseArray = [
                "status" => false,
                "message" => "Contact Us Not Found",
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }

    function apiGetPublicContactUsDetailsSheet(Request $request)
    {

        $rules = [
            'selected_month' => 'required|integer|between:1,12',
            'selected_year' => 'required|after:2023',
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
        } else {

            $contactUsList = PublicContactUsModel::select(
                'name',
                'email_id',
                'mobile_no',
                'message',
                'file_url',
                'created_at'
            )
                ->whereYear('created_at', $request->selected_year)
                ->whereMonth('created_at', $request->selected_month)
                ->get();

            if ($contactUsList->isEmpty()) {

                $responseArray = [
                    "status" => false,
                    "message" => "Contact Us List Not Found",
                    "data" => []
                ];

                return response()->json($responseArray);

            } else {

                // creation of excel sheet started

                $headers = array_keys($contactUsList->first()->toArray());

                array_unshift($headers, 'Index');

                foreach ($headers as &$value) {
                    $value = ucwords(str_replace('_', ' ', $value));
                }
                unset($value);

                // Create a new Spreadsheet object
                $spreadsheet = new Spreadsheet();

                // Get the active sheet
                $sheet = $spreadsheet->getActiveSheet();

                // Add headers from the $headers array to the first row
                $sheet->fromArray([$headers], null, 'A1');

                // Initialize the row index
                $row = 2;
                $index = 1; // New index variable

                // Set styles for header row (make them bold, align center, and yellow background)
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']]
                ]);

                // Loop through the collection to add data to subsequent rows
                foreach ($contactUsList as $item) {

                    $data = $item->toArray();

                    $data['created_at'] = Carbon::parse($data['created_at'])->format('Y-m-d h:i A');

                    // Get the item's attributes dynamically
                    $rowData = array_values($data);

                    $sheet->setCellValue('A' . $row, $index);

                    // Add item's data to each row
                    $sheet->fromArray([array_values($rowData)], null, 'B' . $row);

                    $row++;
                    $index++;
                }

                // Get the highest row and column index
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Loop through each cell and set 'Number' format for numeric cells
                for ($row = 1; $row <= $highestRow; $row++) {
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        $cellValue = $sheet->getCell($col . $row)->getValue();
                        if (is_numeric($cellValue)) {
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
                        }
                    }
                }

                // Set borders for all active cells
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        ],
                    ],
                ];

                $sheet->getStyle('A1:' . $highestColumn . $highestRow)->applyFromArray($styleArray);

                // Auto-size columns
                foreach (range('A', 'Z') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }

                $writer = new Xlsx($spreadsheet);

                $directoryPath = app()->basePath('public/contact-us-sheet');

                if (!is_dir($directoryPath)) {
                    // Directory doesn't exist, create it
                    mkdir($directoryPath, 0755, true); // You can adjust the permission mode (e.g., 0755) as needed
                }

                $date = date('Y-m-d_His');

                // Create the new filename by adding the date before the file extension
                // $file_name = 'Quotations' . '-' . $date;

                $file_name = 'Packaging Hub Contact Us Sheet';

                $sheetName = $file_name . '.xlsx';

                // Save the Sheet to a file
                // $sheetFilePath = app()->basePath('public/contact-us-sheet/') . $sheetName;
                // $writer->save($sheetFilePath);

                // creation of excel sheet ended

                // Create a streamed response
                $response = new StreamedResponse(function () use ($writer) {

                    // Set the appropriate headers for Excel file download
                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                    header('Content-Disposition: attachment;filename="Packaging_Hub_Contact_Us_Sheet.xlsx"');

                    // Output the Excel content directly to the output buffer
                    $writer->save('php://output');
                });

                // Return the streamed response
                return $response;

                // $data['count'] = $contactUsList->count() ?? 0;
                // $data['path'] = asset('public/contact-us-sheet') . DIRECTORY_SEPARATOR . str_replace(' ', '%20', $sheetName);

                // $responseArray = [
                //     "status" => true,
                //     "message" => "Contact Us List Found",
                //     "data" => $data
                // ];

                // return response()->json($responseArray);

            }
        }
    }

    function apiPostPublicInquiryDetails(Request $request)
    {

        $rules = [
            'name' => 'required',
            'request_title' => 'required',
            'email_id' => 'email|required',
            'mobile_no' => 'required|size:10',
            'message' => 'required'
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "There is error while filling the form",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $inquiry = new PublicCustomerInquiryModel;
            $inquiry->request_title = $request->request_title;
            $inquiry->name = $request->name;
            $inquiry->email_id = $request->email_id;
            $inquiry->mobile_no = $request->mobile_no;
            $inquiry->message = $request->message;
            $inquiry->created_at = Carbon::now()->addHours(5)->addMinutes(30);
            $inquiry->save();



            // Mail trigger

            $subject = 'Inquery';
            $greeting = "Hello Admin!";
            $content = 'Success!';
            $data = [
                "name" => $request->name,
                "email_id" => $request->email_id,

                "mobile" => $request->mobile_no,
                "meaasge" => $request->message
            ];

            $email = ADMIN_MAIL;
            Mail::to($email)->send(new AdminInqueryMail($subject, $greeting, $content, $data));

            Mail::to($request->email_id)->send(new PublicInqueryMail($subject, $greeting, $content, $data));

            $responseArray = [
                "status" => true,
                "message" => "Inquiry Details Added Successfully!",
                "data" => []
            ];

            return response()->json($responseArray);
        }
    }

    public function apiPublicPostViewCategoryWiseModelList(Request $request)
    {
        $rules = [
            'category_id' => 'sometimes|integer|exists:categories,category_id',
            'company_name' => 'required|string|exists:product_variants,company_name',
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Please provide valid inputs",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {
            $category_id = $request->category_id;

            $company_name = $request->company_name;

            $query = Category::select('category_id', 'category_name', 'category_parent_id', 'image')
                ->join('product_categories', 'product_categories.category', 'categories.category_id')
                ->join('product', 'product.product_unique', 'product_categories.product_unique')
                ->join('product_variants', 'product_variants.product_unique', 'product.product_unique')
                ->where('product.is_active', 1)
                ->where('product_variants.company_name', $company_name);

            if ($category_id) {
                $query = $query->where('category_id', $category_id)->orWhere('category_parent_id', $category_id);
            }

            $categories = $query
                ->orderBy('category_parent_id', 'asc')
                ->distinct('category_id')->get();

            $output = [];

            foreach ($categories as $category) {

                if ($category->category_parent_id == 0) {

                    $output[] = array(
                        'category_id' => $category->category_id,
                        'category_name' => $category->category_name,
                        'image' => asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $category->image),
                        'children' => $this->__fetchChildCategoryAndProduct($categories, $category->category_id, $company_name)
                    );
                }
            }

            if (!empty($output)) {

                $responseArray = [
                    "status" => true,
                    "message" => "Compare categories list found",
                    "data" => $output
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => false,
                    "message" => "Unable to perform this action!",
                    "data" => [
                        "error" => ["Compare categories list not found"]
                    ]
                ];

                return response()->json($responseArray);
            }
        }
    }

    // PRODUCT SEARCHING CODE BLOCK STARTS HERE
    public function apiGetPublicViewSearchProductsList(Request $request)
    {

        $rules = [
            'search_text' => 'required|string',
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
        } else {

            $search_text = $request->search_text;

            $productQuery = Product::select(
                'product.product_name',
                'product.product_unique',
                'product.is_active'
            )
                ->where('product.is_active', IS_ACTIVE_YES)
                ->where(function ($query) use ($search_text) {
                    $query->where('product.product_name', 'like', '%' . $search_text . '%')
                        ->orWhere('categories.category_name', 'like', '%' . $search_text . '%')
                        ->orWhere('product_variants.company_name', 'like', '%' . $search_text . '%')
                        ->orWhere('product_variants.product_model', 'like', '%' . $search_text . '%');
                });

            $productQuery->join('product_categories', 'product.product_unique', '=', 'product_categories.product_unique')
                ->join('categories', 'product_categories.category', 'categories.category_id')
                ->join('product_variants', 'product.product_unique', 'product_variants.product_unique');

            $products = $productQuery->distinct('product.product_unique')->paginate(24)->toArray();

            foreach ($products['data'] as &$product) {

                $image = ProductVariantImagesModel::select('image')
                    ->where('product_unique', $product['product_unique'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($image) {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
                } else {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
                }

                // product variants

                $variant = ProductVariantSizeModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['product_height_inch'] = $variant->product_height_inch;
                $product['product_height_mm'] = $variant->product_height_mm;
                $product['product_width_inch'] = $variant->product_width_inch;
                $product['product_width_mm'] = $variant->product_width_mm;
                $product['product_length_inch'] = $variant->product_length_inch;
                $product['product_length_mm'] = $variant->product_length_mm;
                $product['product_weight'] = $variant->product_weight;

                $variant = ProductVariantModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['rating'] = $variant->rating;
            }

            if ($products['total'] > 0) {

                $productsList['current_page'] = $products['current_page'];
                $productsList['per_page'] = $products['per_page'];
                $productsList['total'] = $products['total'];
                $productsList['last_page'] = $products['last_page'];
                $productsList['products_list'] = $products['data'];

                $responseArray = [
                    "status" => true,
                    "message" => "List of Products found",
                    "data" => $productsList,
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => true,
                    "message" => "Products not found",
                    "data" => ['products_list' => []],
                ];

                return response()->json($responseArray);
            }
        }
    }

    function __fetchChildCategoryAndProduct($categories, $parentId, $company_name)
    {

        $output = [];

        foreach ($categories as $category) {

            if ($category->category_parent_id == $parentId) {
                $products = Product::join('product_categories', 'product.product_unique', '=', 'product_categories.product_unique')
                    ->join('categories', 'categories.category_id', '=', 'product_categories.category')
                    ->join('product_variants', 'product.product_unique', '=', 'product_variants.product_unique')
                    ->join('product_variant_images', 'product.product_unique', '=', 'product_variant_images.product_unique') // Adjust table and column names
                    ->where('product.is_active', '=', 1)
                    ->where('categories.category_id', '=', $category->category_id)
                    ->where('product_variants.company_name', '=', $company_name)
                    ->groupBy('product.id', 'product.product_unique', 'product.product_name', 'product_variants.product_model', 'product_variant_images.image')
                    ->select('product.id', 'product.product_unique', 'product.product_name', 'product_variant_images.image', 'product_variants.product_model') // Include image information in the selection
                    ->get();

                // Process the results to organize images as an array within each product
                $processedProducts = [];

                foreach ($products as $product) {
                    $productId = $product->id;

                    // Check if the product is already in the processed array
                    if (!array_key_exists($productId, $processedProducts)) {
                        $processedProducts[$productId] = [
                            'head_category_id' => $parentId,
                            'id' => $productId,
                            'product_unique' => $product->product_unique,
                            'product_name' => $product->product_name,
                            'product_model' => $product->product_model,
                            'images' => [], // Initialize the images array
                        ];
                    }

                    // Add the image URL to the images array for the respective product
                    $processedProducts[$productId]['images'][] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $product->image);
                }

                if ($products->count()) {

                    $output[] = array(
                        'category_id' => $category->category_id,
                        'category_name' => $category->category_name,
                        'category_parent_id' => $category->category_parent_id,
                        'products' => array_values($processedProducts)
                    );
                }
            }
        }
        return $output;
    }

    function __validateJsonArrayInput($input, $fieldName, $filter = 'is_string')
    {
        $fieldData = json_decode($input);

        if (!is_array($fieldData) || empty($fieldData) || in_array(false, array_map($filter, $fieldData), true)) {

            $responseArray = [
                "status" => false,
                "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                "data" => "Invalid $fieldName found"
            ];

            return response()->json($responseArray);
        }

        return $fieldData;
    }

    public function apiPublicPostViewIndustryWiseProductsList(Request $request)
    {
        $rules = [
            'industry_name' => 'sometimes|string',
            'company_name' => 'sometimes|array',
            'sort_by' => 'required|in:sort_by_popularity,sort_by_average_rating,sort_by_latest,sort_by_a_to_z,sort_by_z_to_a',
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
        } else {

            $industry_name = $request->industry_name;

            $productQuery = Product::select(
                'product.product_name',
                'product.product_unique',
                'product.is_active'
            )
                ->join('product_variants', 'product.product_unique', 'product_variants.product_unique')
                ->where('product.is_active', IS_ACTIVE_YES);

            switch ($request->sort_by) {

                case 'sort_by_latest':

                    $productQuery = $productQuery->orderby('product.created_at', 'desc');
                    break;

                case 'sort_by_z_to_a':

                    $productQuery = $productQuery->orderby('product.product_name', 'desc');
                    break;

                case 'sort_by_average_rating':

                    $productQuery = $productQuery->orderby('product_variants.rating', 'desc');
                    break;

                case 'sort_by_popularity':

                    $productQuery = $productQuery
                        ->join('product_tags', 'product.product_unique', '=', 'product_tags.product_unique')
                        ->orderByRaw('CASE WHEN product_tags.tag_id = 2 THEN 0 ELSE 1 END');
                    // ->orderby('product.product_name', 'asc');
                    // ->where('product_tags.tag_id', 2)
                    break;

                default:

                    $productQuery = $productQuery->orderby('product.product_name', 'asc');
                    break;
            }

            if ($industry_name) {

                $productQuery = $productQuery->where(function ($query) use ($industry_name) {
                    $query->where('product_variants.industry_name', $industry_name);

                    if (Str::lower($industry_name) == 'food and pharmaceutical') {
                        $query->orWhere(function ($subquery) {
                            $subquery->whereIn('product_variants.industry_name', ['Food and pharmaceutical', 'Food and pharmaceutical industry', 'Food']);
                        });
                    }
                });
            }

            $companyNames = $request->company_name;

            if (!empty($companyNames)) {
                // Convert company names to lowercase and trim whitespace
                $companyNames = array_map('strtolower', $companyNames);
                $companyNames = array_map('trim', $companyNames);

                // Use whereIn to filter by multiple company names
                $productQuery = $productQuery->whereIn(DB::raw('LOWER(product_variants.company_name)'), $companyNames);
            }

            $products = $productQuery->distinct('product.product_unique')->paginate(24)->toArray();

            foreach ($products['data'] as &$product) {

                $image = ProductVariantImagesModel::select('image')
                    ->where('product_unique', $product['product_unique'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($image) {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
                } else {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
                }

                // product variants

                $variant = ProductVariantSizeModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['product_height_inch'] = $variant->product_height_inch;
                $product['product_height_mm'] = $variant->product_height_mm;
                $product['product_width_inch'] = $variant->product_width_inch;
                $product['product_width_mm'] = $variant->product_width_mm;
                $product['product_length_inch'] = $variant->product_length_inch;
                $product['product_length_mm'] = $variant->product_length_mm;
                $product['product_weight'] = $variant->product_weight;

                $variant = ProductVariantModel::select('*')
                    ->where('product_unique', $product['product_unique'])
                    ->first();

                $product['rating'] = $variant->rating;
            }

            if ($industry_name) {

                $companies = ProductVariantModel::orderBy('company_name', 'asc')->where(function ($query) use ($industry_name) {

                    $query->where('product_variants.industry_name', $industry_name);

                    if (Str::lower($industry_name) == 'food and pharmaceutical') {
                        $query->orWhere(function ($subquery) {
                            $subquery->whereIn('product_variants.industry_name', ['Food and pharmaceutical', 'Food and pharmaceutical industry', 'Food']);
                        });
                    }

                });
            } else {

                $companies = ProductVariantModel::orderBy('company_name', 'asc');
            }

            $companies = $companies->pluck('company_name')->unique()->values();

            if ($products['total'] > 0) {

                $productsList['current_page'] = $products['current_page'];
                $productsList['per_page'] = $products['per_page'];
                $productsList['total'] = $products['total'];
                $productsList['last_page'] = $products['last_page'];
                $productsList['companies_list'] = $companies;
                $productsList['products_list'] = $products['data'];

                $responseArray = [
                    "status" => true,
                    "message" => "List of Products found",
                    "data" => $productsList,
                ];

                return response()->json($responseArray);
            } else {

                $productsList['current_page'] = $products['current_page'];
                $productsList['per_page'] = $products['per_page'];
                $productsList['total'] = $products['total'];
                $productsList['last_page'] = $products['last_page'];
                $productsList['companies_list'] = $companies;
                $productsList['products_list'] = [];

                $responseArray = [
                    "status" => true,
                    "message" => "Products not found",
                    "data" => $productsList,
                ];

                return response()->json($responseArray);
            }
        }
    }
}
