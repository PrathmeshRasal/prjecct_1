<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
use App\Rules\PositiveNumbersInCsvRule;
use Illuminate\Http\Request;

use App\Models\Product;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidatedIsActiveFieldRule;
use App\Rules\ValidateIsActiveFieldIsZeroRule;
use Illuminate\Support\Str;

require_once app_path('Helpers/Constants.php');

class AdminProductController extends Controller
{
    //VIEW ALL PRODUCT DETAILS FUNCTION
    function adminApiGetViewAllProducts(Request $request)
    {

        $rules = [
            'search_text' => 'sometimes|nullable|string',
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

            $search_text = trim($request->search_text);

            // $get_all_product_details = Product::select('id', 'product_name', 'product_unique', 'is_active')
            // ->orderBy('id', 'desc')
            // ->paginate(25)
            // ->toArray();

                $query = Product::select(
                    'product.id',
                    'product.product_name',
                    'product.product_unique',
                    'product.is_active'
                );

                if (!empty($search_text)) {

                    $query ->where('product.is_active', IS_ACTIVE_YES)
                    ->where(function ($query) use ($search_text) {
                        $query->where('product.product_name', 'like', '%' . $search_text . '%')
                            ->orWhere('categories.category_name', 'like', '%' . $search_text . '%')
                            ->orWhere('product_variants.company_name', 'like', '%' . $search_text . '%')
                            ->orWhere('product_variants.product_model', 'like', '%' . $search_text . '%');
                    });
    
                }
                   
                $query->join('product_categories', 'product.product_unique', '=', 'product_categories.product_unique')
                    ->join('categories', 'product_categories.category', 'categories.category_id')
                    ->join('product_variants', 'product.product_unique', 'product_variants.product_unique')
                    ->distinct('product.product_unique');
                    
                    $get_all_product_details = $query->paginate(25)->toArray();
                    
                    // dd($get_all_product_details);

            foreach ($get_all_product_details['data'] as &$product) {

                $image = ProductVariantImagesModel::select('image')
                    ->where('product_unique', $product['product_unique'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                if ($image) {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . $image->image);
                } else {

                    $product['image'] = asset(IMAGE_PATH_PRODUCT_IMAGES . '/' . DEFAULT_PRODUCT_IMAGE);
                }
            }

            if ($get_all_product_details['total'] > 0) {
                $productList['products_list'] = $get_all_product_details['data'];
                $productList['current_page'] = $get_all_product_details['current_page'];
                $productList['per_page'] = $get_all_product_details['per_page'];
                $productList['total'] = $get_all_product_details['total'];
                $productList['last_page'] = $get_all_product_details['last_page'];

                $responseArray = [
                    "status" => true,
                    "message" => "List of Products",
                    "data" => $productList
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => false,
                    "message" => "Products Not Found",
                    "data" => []
                ];

                return response()->json($responseArray);
            }
        }
    }

    //ADD NEW PRODUCT FUNCTION
    function adminApiPostAddNewProduct(Request $request)
    {

        $rules = [
            'product_name' => 'required|max:300|unique:product,product_name',
            'is_active' => ['required', 'numeric', new ValidatedIsActiveFieldRule],
            'product_images.*.image' => 'required|file|mimes:webp,jpg,jpeg,png|max:4096', // 4096 = 4 mb
            'machine_type' => 'required',
            'company_name' => 'required',
            'country' => 'required',
            'product_type' => 'required',
            'product_model' => 'required',
            'industry_name' => 'required',
            'product_width_mm' => 'nullable',
            'product_width_inch' => 'nullable',
            'product_height_mm' => 'nullable',
            'product_height_inch' => 'nullable',
            'product_length_mm' => 'nullable',
            'product_length_inch' => 'nullable',
            'product_weight' => 'nullable',
            'product_tags' => 'required|regex:/^\d+(,\d+)*$/|exists:tags,id',
            'highlights' => 'nullable|max:10000|json',
            'categories' => ['required', new PositiveNumbersInCsvRule, 'exists:categories,category_id'],
            'product_specs' => 'json',
            'brochure' => 'sometimes|required|mimes:pdf|max:5120', // 5 MB
            'product_intro_video' => 'sometimes|required|mimes:mp4|max:10240', // 10 MB
        ];

        $errorMessages = [
            'product_name.exists' => "Product name already taken",
            "product_tags.regex" => "Product tags not in correct format, only comma seperated values accepted",
            "product_specs" => "Products specs are not in correct format",
            'categories.exists' => "Category does not exists",
            'product_tags.exists' => "Tag(s) does not exists",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            // PRODUCT DATA ADD CODE
            $product = new Product;
            $product->product_name = $request->product_name;

            // this value should be unique
            $minValue = MIN_PRODUCT_INT_VALUE;
            $maxValue = MAX_PRODUCT_INT_VALUE;
            $product_unique = random_int($minValue, $maxValue);

            $product->product_unique = $product_unique;
            $product->is_active = $request->is_active;
            $product->save();


            // PRODUCT VARIANT DATA CODE ADD

            $productVariant = new ProductVariantModel;
            $productVariant->product_unique = $product_unique;
            $productVariant->machine_type = $request->machine_type;
            $productVariant->company_name = $request->company_name;
            $productVariant->country = $request->country;
            $productVariant->product_type = $request->product_type;
            $productVariant->product_model = $request->product_model;
            $productVariant->industry_name = $request->industry_name;
            $productVariant->save();


            // PRODUCT SIZE DATA CODE ADD

            $productSize = new ProductVariantSizeModel;
            $productSize->product_unique = $product_unique;
            $productSize->product_height_inch = $request->product_height_inch;
            $productSize->product_height_mm = $request->product_height_mm;
            $productSize->product_width_inch = $request->product_width_inch;
            $productSize->product_width_mm = $request->product_width_mm;
            $productSize->product_length_mm = $request->product_length_mm;
            $productSize->product_length_inch = $request->product_length_inch;
            $productSize->product_weight = $request->product_weight;
            $productSize->save();


            // PRODUCT IMAGE DATA CODE ADD

            if (!is_null($request->product_images)) {

                for ($i = 0; $i < sizeof($request->product_images); $i++) {

                    // create file name

                    $productNameSlug = Str::slug($product->product_name . '-' . $product_unique, '-');

                    // create final file name

                    $fileName = $productNameSlug . '-' . time() . '_' . $i . '.' . $request->product_images[$i]["image"]->extension();

                    // move image to right folder

                    $request->product_images[$i]["image"]->move(IMAGE_PATH_PRODUCT_IMAGES, $fileName);
                    // save details in the database
                    $productVariantImage = new ProductVariantImagesModel;
                    $productVariantImage->product_unique = $product_unique;
                    $productVariantImage->product_variant_id = $productVariant->id;
                    $productVariantImage->image = $fileName;
                    $productVariantImage->save();
                }
            } else {
                // admin didn't send any image
                $productVariantImage = new ProductVariantImagesModel;
                $productVariantImage->product_unique = $product_unique;
                $productVariantImage->product_variant_id = $productVariant->id;
                $productVariantImage->image = DEFAULT_PRODUCT_IMAGE;
                $productVariantImage->save();
            }


            // PRODUCT BROCHURE DATA CODE ADD

            if (!is_null($request->brochure)) {

                // create file name

                $productNameSlug = Str::slug($product->product_name . '-' . $product_unique, '-');

                // create final file name

                $fileName = $productNameSlug . '-' . 'brochure' . '-' . time() . '.' . $request->brochure->extension();

                // move pdf to right folder

                $request->brochure->move(PRODUCT_BROCHURE_PDFS, $fileName);
                // save details in the database
                $productVariantBrochure = new ProductVariantBrochuresModel;
                $productVariantBrochure->product_unique = $product_unique;
                $productVariantBrochure->product_variant_id = $productVariant->id;
                $productVariantBrochure->brochure = $fileName;
                $productVariantBrochure->save();
            }


            // PRODUCT INTRO VIDEO DATA CODE ADD

            if (!is_null($request->product_intro_video)) {

                // create file name

                $productNameSlug = Str::slug($product->product_name . '-' . $product_unique, '-');

                // create final file name

                $fileName = $productNameSlug . '-' . 'intro-video' . '-' . time() . '.' . $request->product_intro_video->extension();

                // move video to right folder

                $request->product_intro_video->move(PRODUCT_VIDEOS, $fileName);
                // save details in the database
                $productVariantIntroVideo = new ProductVariantVideosModel;
                $productVariantIntroVideo->product_unique = $product_unique;
                $productVariantIntroVideo->product_variant_id = $productVariant->id;
                $productVariantIntroVideo->video = $fileName;
                $productVariantIntroVideo->video_type = 'INTRO';
                $productVariantIntroVideo->save();
            }


            //PRODUCT HIGHLIGHT DATA CODE ADD

            $dataArray = json_decode($request->highlights, true);

            foreach ($dataArray as $highlight) {

                $productVariantHighlight = new ProductVariantHighlightsModel;
                $productVariantHighlight->product_unique = $product_unique;
                $productVariantHighlight->product_variant_id = $productVariant->id;
                $productVariantHighlight->highlight_text = $highlight;
                $productVariantHighlight->save();
            }

            // NOW PRODUCT SAVING CATEGORIES BELOW

            $productCategories = explode(",", $request->categories);

            foreach ($productCategories as $category) {
                $productCategory = new ProductCategoryModel;
                $productCategory->product_unique = $product_unique;
                $productCategory->category = $category;
                $productCategory->save();
            }

            // PRODUCT SPACIFICATION DATA ADD CODE

            if ($request->product_specs) {

                $dataArray = json_decode($request->product_specs, true);

                // Now you can work with the PHP array
                foreach ($dataArray as $item) {
                    $productSpek = new ProductSpecsModel;
                    $productSpek->product_unique = $product_unique;
                    $productSpek->product_variant_id = $productVariant->id;
                    $productSpek->specification_heading = $item["head"];
                    $productSpek->is_active = IS_ACTIVE_YES;
                    $productSpek->save();

                    foreach ($item['arr'] as $subItem) {
                        $productSpekValues = new ProductSpecsKeyValueModel;
                        $productSpekValues->product_unique = $product_unique;
                        $productSpekValues->product_variant_id = $productVariant->id;
                        $productSpekValues->product_spec_id = $productSpek->id;
                        $productSpekValues->spec_key = $subItem["key"];
                        $productSpekValues->spec_value = $subItem["value"];
                        $productSpekValues->is_active = IS_ACTIVE_YES;
                        $productSpekValues->save();
                    }
                }
            }

            // PRODUCT TAGS DATA ADD CODE

            $product_tags = explode(',', $request->product_tags);

            foreach ($product_tags as $product_tag) {

                $tag = new ProductTagModel;
                $tag->product_unique = $product_unique;
                $tag->tag_id = $product_tag;
                $tag->save();
            }

            $responseArray = [
                "status" => true,
                "message" => "Product Details Added Successfully!",
                "data" => $product
            ];

            return response()->json($responseArray);
        }
    }

    //VIEW SINGLE PRODUCT DETAILS FUNCTION
    function adminApiGetViewSingleProduct(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'product_unique' => 'required|numeric',
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

                // fetching product tags

                $productTags = ProductTagModel::select('tag_id')
                    ->where('product_unique', $request->product_unique)
                    ->get();

                $tags = [];

                foreach ($productTags as $tag) {

                    $tags[] = [
                        //'tag_name'  =>  $tag->tag->title,
                        'tag_id' => $tag->tag_id
                    ];
                }

                $product = array(
                    'product' => array(
                        'product_name' => $product_data->product_name,
                        'categories' => $productCategory,
                        'productvariant' => $productVariant,
                        'productsize' => $productVariantSize,
                        'is_active' => $product_data->is_active,
                    ),
                    'tags' => $tags,
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

    //PRODUCT EDIT FUNCTION
    function adminApiPostEditProduct(Request $request)
    {

        $rules = [
            'product_unique' => 'required',
            'product_name' => 'required',
            'machine_type' => 'required',
            'company_name' => 'required',
            'country' => 'required',
            'product_type' => 'required',
            'product_model' => 'required',
            'industry_name' => 'required',
            'product_width_mm' => 'required',
            'product_width_inch' => 'required',
            'product_height_mm' => 'required',
            'product_height_inch' => 'required',
            'product_length_mm' => 'required',
            'product_length_inch' => 'required',
            'product_weight' => 'required',
            'highlights' => 'required|max:10000|json',
            'categories' => ['required', new PositiveNumbersInCsvRule, 'exists:categories,category_id'],
            'product_specs' => 'json',
            'product_variant_id' => 'required',
            'is_active' => ['required', 'numeric', new ValidatedIsActiveFieldRule],
            'product_tags' => 'required|regex:/^\d+(,\d+)*$/',
            'brochure' => 'sometimes|required|mimes:pdf|max:5120', // 5 MB
            'product_intro_video' => 'sometimes|required|mimes:mp4|max:10240', // 10 MB
        ];

        $errorMessages = [
            'product_unique.unique' => 'Product name already exists',
            'product_unique.exists' => 'Product does not exists',
            "product_tags.regex" => "Product tags not in correct format, only comma seperated values accepted",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "There is error while filling the form",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $duplicatecategory = Product::where('product_name', $request->product_name)
                ->where('product_unique', '!=', $request->product_unique)
                ->first();

            if (!$duplicatecategory) {
                //update product details

                $affectedRows = Product::where('product_unique', $request->product_unique)
                    ->update([
                        'product_name' => $request->product_name,
                        'is_active' => $request->is_active,
                    ]);

                //update product variant details

                $productVariantRows = ProductVariantModel::where('product_unique', $request->product_unique)
                    ->update([
                        'machine_type' => $request->machine_type,
                        'country' => $request->country,
                        'company_name' => $request->company_name,
                        'product_type' => $request->product_type,
                        'product_model' => $request->product_model,
                        'industry_name' => $request->industry_name,
                    ]);

                //update product variant size details

                $productVariantRows = ProductVariantSizeModel::where('product_unique', $request->product_unique)
                    ->update([
                        'product_height_inch' => $request->product_height_inch,
                        'product_height_mm' => $request->product_height_mm,
                        'product_width_inch' => $request->product_width_inch,
                        'product_width_mm' => $request->product_width_mm,
                        'product_length_inch' => $request->product_length_inch,
                        'product_length_mm' => $request->product_length_mm,
                        'product_weight' => $request->product_weight,
                    ]);

                // update product categories

                // delete old product categories

                $productCategoriesDelete = ProductCategoryModel::where('product_unique', $request->product_unique)->delete();

                $productCategories = explode(",", $request->categories);

                foreach ($productCategories as $category) {

                    $productCategory = new ProductCategoryModel;
                    $productCategory->product_unique = $request->product_unique;
                    $productCategory->category = $category;
                    $productCategory->save();
                }

                // update product Highlight

                // delete old product Highlight

                $productHighlightDelete = ProductVariantHighlightsModel::where('product_unique', $request->product_unique)->delete();

                $dataArray = json_decode($request->highlights, true);

                foreach ($dataArray as $highlight) {

                    $productVariantHighlight = new ProductVariantHighlightsModel;
                    $productVariantHighlight->product_unique = $request->product_unique;
                    $productVariantHighlight->product_variant_id = $request->product_variant_id;
                    $productVariantHighlight->highlight_text = $highlight;
                    $productVariantHighlight->save();
                }

                // update product brochure

                // delete old product brochure

                if (!is_null($request->brochure)) {

                    $product_brochure_data = ProductVariantBrochuresModel::select('*')
                        ->where(['product_unique' => $request->product_unique])
                        ->first();

                    if ($product_brochure_data) {

                        $filePath = public_path(PRODUCT_BROCHURE_PDFS . '/' . $product_brochure_data->brochure);

                        if (File::exists($filePath)) {

                            // Delete the file
                            File::delete($filePath);
                        }

                        $productBrochureDelete = ProductVariantBrochuresModel::where('product_unique', $request->product_unique)->delete();
                    }

                    // create file name

                    $productNameSlug = Str::slug($request->product_name . '-' . $request->product_unique, '-');

                    // create final file name

                    $fileName = $productNameSlug . '-' . 'brochure' . '-' . time() . '.' . $request->brochure->extension();

                    // move pdf to right folder

                    $request->brochure->move(PRODUCT_BROCHURE_PDFS, $fileName);
                    // save details in the database
                    $productVariantBrochure = new ProductVariantBrochuresModel;
                    $productVariantBrochure->product_unique = $request->product_unique;
                    $productVariantBrochure->product_variant_id = $request->product_variant_id;
                    $productVariantBrochure->brochure = $fileName;
                    $productVariantBrochure->save();
                }


                // update product intro video

                // delete old product intro video

                if (!is_null($request->product_intro_video)) {

                    $product_intro_video_data = ProductVariantVideosModel::select('*')
                        ->where(['product_unique' => $request->product_unique, 'video_type' => 'INTRO'])
                        ->first();

                    if ($product_intro_video_data) {

                        $filePath = public_path(PRODUCT_VIDEOS . '/' . $product_intro_video_data->video);

                        if (File::exists($filePath)) {

                            // Delete the file
                            File::delete($filePath);
                        }

                        $productIntroVideoDelete = ProductVariantVideosModel::where(['product_unique' => $request->product_unique, 'video_type' => 'INTRO'])->delete();
                    }

                    // create file name
                    $productNameSlug = Str::slug($request->product_name . '-' . $request->product_unique, '-');

                    // create final file name

                    $fileName = $productNameSlug . '-' . 'intro-video' . '-' . time() . '.' . $request->product_intro_video->extension();

                    // move video to right folder

                    $request->product_intro_video->move(PRODUCT_VIDEOS, $fileName);
                    // save details in the database
                    $productVariantIntroVideo = new ProductVariantVideosModel;
                    $productVariantIntroVideo->product_unique = $request->product_unique;
                    $productVariantIntroVideo->product_variant_id = $request->product_variant_id;
                    $productVariantIntroVideo->video = $fileName;
                    $productVariantIntroVideo->video_type = 'INTRO';
                    $productVariantIntroVideo->save();
                }

                // update Specs & SpecsKeyValue

                // delete old product Specs & SpecsKeyValue

                $productSpecsDelete = ProductSpecsModel::where('product_unique', $request->product_unique)->delete();

                $productSpecsKeyValueDelete = ProductSpecsKeyValueModel::where('product_unique', $request->product_unique)->delete();

                if ($request->product_specs) {

                    $dataArray = json_decode($request->product_specs, true);

                    // Now you can work with the PHP array
                    foreach ($dataArray as $item) {
                        $productSpek = new ProductSpecsModel;
                        $productSpek->product_unique = $request->product_unique;
                        $productSpek->product_variant_id = $request->product_variant_id;
                        $productSpek->specification_heading = $item["head"];
                        $productSpek->is_active = IS_ACTIVE_YES;
                        $productSpek->save();

                        foreach ($item['arr'] as $subItem) {
                            $productSpekValues = new ProductSpecsKeyValueModel;
                            $productSpekValues->product_unique = $request->product_unique;
                            $productSpekValues->product_variant_id = $request->product_variant_id;
                            $productSpekValues->product_spec_id = $productSpek->id;
                            $productSpekValues->spec_key = $subItem["key"];
                            $productSpekValues->spec_value = $subItem["value"];
                            $productSpekValues->is_active = IS_ACTIVE_YES;
                            $productSpekValues->save();
                        }
                    }
                }

                // Update Product Tags Details

                // Delete Product Tags

                $productTags = ProductTagModel::where('product_unique', $request->product_unique)->delete();

                $product_tags = explode(',', $request->product_tags);

                foreach ($product_tags as $product_tag) {

                    $tag = new ProductTagModel;
                    $tag->product_unique = $request->product_unique;
                    $tag->tag_id = $product_tag;
                    $tag->save();
                }

                if ($affectedRows > 0) {
                    $responseArray = [
                        "status" => true,
                        "message" => "Product updated successfully!",
                        "data" => [
                            "product_name" => $request->product_name
                        ]
                    ];

                    return response()->json($responseArray);
                } else {
                    $responseArray = [
                        "status" => false,
                        "message" => "Unable to perform this action!",
                        "data" => [
                            "error" => ["Product does not exists, please try again!"]
                        ]
                    ];

                    return response()->json($responseArray);
                }
            } else {

                $responseArray = [
                    "status" => false,
                    "message" => "Unable to perform this action!",
                    "data" => [
                        "error" => ["Product name already exists"]
                    ]
                ];

                return response()->json($responseArray);
            }
        }
    }

    //SOFT DELETE PRODUCT FUNCTION
    function adminApiPostSoftDeleteProduct(Request $request)
    {

        //return $request->product_unique;

        $rules = array(
            'product_unique' => 'required|exists:product,product_unique',
            //'is_active'       =>  ['required','numeric', new ValidateIsActiveFieldIsZeroRule]
        );

        $messages = array(
            'product_unique.exists' => 'Product Unique Does Not Exists'
        );

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "There was error, while deleting product",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $affectedRows = Product::where('product_unique', $request->product_unique)
                ->update(['is_active' => "0"]);

            //$affectedRows = Product::where('product_unique', $request->product_unique)
            //  ->delete();

            //$table->foreign('product_unique')
            //  ->references('product_unique')
            //->on('product')
            //->onDelete('cascade');

            if ($affectedRows) {

                //$ProductVariantModelRows = ProductVariantModel::where('product_unique', $request->product_unique)
                //  ->delete();

                //$ProductVariantSizeModelRows = ProductVariantSizeModel::where('product_unique', $request->product_unique)
                // ->delete();
                //$ProductVariantImagesModelRows = ProductVariantImagesModel::where('product_unique', $request->product_unique)
                //    ->delete();
                //$ProductVariantHighlightsModelRows = ProductVariantHighlightsModel::where('product_unique', $request->product_unique)
                //    ->delete();
                //$ProductCategoryModelRows = ProductCategoryModel::where('product_unique', $request->product_unique)
                //    ->delete();
                //$ProductSpecsModelRows = ProductSpecsModel::where('product_unique', $request->product_unique)
                //    ->delete();
                //$ProductSpecsKeyValueModelRows = ProductSpecsKeyValueModel::where('product_unique', $request->product_unique)
                //  ->delete();

                $responseArray = [
                    "status" => true,
                    "message" => "Product Delete Successfully",
                    "data" => []
                ];

                return response()->json($responseArray);
            } else {
                $responseArray = [
                    "status" => false,
                    "message" => "Unable to delete Product!",
                    "data" => [
                        "error" => "Product does not exists, please try again!"
                    ]
                ];

                return response()->json($responseArray);
            }
        }
    }

    //GET PRODUCT SPECIFICATION FUNCTION
    function adminApiGetAllProductSpecification(Request $request)
    {

        $rules = [
            'product' => 'required|exists:product,product_unique',
        ];

        $errorMessages = [
            "product.exists" => "Requested Product does not exists",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $productSpecs = ProductSpecsModel::select(
                'id',
                'product_unique',
                'product_variant_id',
                'specification_heading',
                'is_active'
            )->where(['product_unique' => $request->product])
                ->get();

            if ($productSpecs->count() > 0) {

                $specs = [];

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
                        ->get();

                    $productSpecKeyValue = [];

                    foreach ($productSpecsValues as $productSpecs) {

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

                    $specs[] = array(
                        'id' => $spec->id,
                        'product' => $spec->product_unique,
                        'variant' => $spec->product_variant_id,
                        'is_active' => $spec->is_active,
                        'specification_heading' => $spec->specification_heading,
                        'specifications' => $productSpecKeyValue
                    );
                }

                $responseArray = [
                    "status" => true,
                    "message" => "Product specifications found",
                    "data" => $specs
                ];

                return response()->json($responseArray);
            } else {
                $responseArray = [
                    "status" => false,
                    "message" => "Product specification does not exists",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            }
        }
    }

    //GET PRODUCT HIGHLIGHTS FUNCTION
    function adminApiGetAllProductHighlights(Request $request)
    {

        $rules = [
            'product' => 'required|exists:product,product_unique',
        ];

        $errorMessages = [
            "product.exists" => "Requested Product does not exists",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $productHighlights = ProductVariantHighlightsModel::select(
                'id',
                'product_unique',
                'product_variant_id',
                'highlight_text'
            )
                ->where(['product_unique' => $request->product])
                ->orderBy('id', 'desc')
                ->get();

            $output = [];

            foreach ($productHighlights as $high) {

                $output[] = array(
                    'id' => $high->id,
                    'product' => $high->product_unique,
                    'variant' => $high->product_variant_id,
                    'highlight_text' => $high->highlight_text,
                );
            }

            if (!empty ($output)) {

                $responseArray = [
                    "status" => true,
                    "message" => "Product Highlights found",
                    "data" => $output
                ];

                return response()->json($responseArray);
            } else {
                $responseArray = [
                    "status" => false,
                    "message" => "Product Highlights does not exists",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            }
        }
    }

    //GET PRODUCT IMAGE DETAILS FUNCTION
    function adminApiGetAllProductImageDetails(Request $request)
    {

        $rules = [
            'product' => 'required|exists:product,product_unique',
        ];

        $errorMessages = [
            "product.exists" => "Requested Product does not exists",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $productImages = ProductVariantImagesModel::select(
                'id',
                'product_unique',
                'product_variant_id',
                'image'
            )
                ->where(['product_unique' => $request->product])
                ->orderBy('id', 'asc')
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

            if (!empty ($imageoutput)) {

                $responseArray = [
                    "status" => true,
                    "message" => "Product Images found",
                    "data" => $imageoutput
                ];

                return response()->json($responseArray);
            } else {
                $responseArray = [
                    "status" => false,
                    "message" => "Product Images does not exists",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            }
        }
    }

    //POST PRODUCT IMAGE DETAILS FUNCTION
    function adminApiPostProductImageDetails(Request $request)
    {

        $rules = [
            'product_unique' => 'required|exists:product,product_unique',
            'product_images.*.image' => 'required|file|mimes:webp,jpg,jpeg,png|max:4096', // 4096 = 4 mb
            'product_variant_id' => 'required'
        ];

        $errorMessages = [];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {



            $product = Product::where('product_unique', $request->product_unique)->first();

            //return $product->product_name;

            if (!is_null($request->product_images)) {

                for ($i = 0; $i < sizeof($request->product_images); $i++) {

                    // create file name

                    $productNameSlug = Str::slug($product->product_name . '-' . $request->product_unique, '-');

                    // create final file name

                    $fileName = $productNameSlug . '-' . time() . '_' . $i . '.' . $request->product_images[$i]["image"]->extension();

                    // move image to right folder

                    $request->product_images[$i]["image"]->move(IMAGE_PATH_PRODUCT_IMAGES, $fileName);
                    // save details in the database
                    $productVariantImage = new ProductVariantImagesModel;
                    $productVariantImage->product_unique = $request->product_unique;
                    $productVariantImage->product_variant_id = $request->product_variant_id;
                    $productVariantImage->image = $fileName;
                    $productVariantImage->save();
                }
            } else {
                // admin didn't send any image
                $productVariantImage = new ProductVariantImagesModel;
                $productVariantImage->product_unique = $request->product_unique;
                $productVariantImage->product_variant_id = $request->product_variant_id;
                $productVariantImage->image = DEFAULT_PRODUCT_IMAGE;
                $productVariantImage->save();
            }

            if (!empty ($productVariantImage)) {

                $responseArray = [
                    "status" => true,
                    "message" => "Product Images Added Successfully",
                    "data" => []
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => false,
                    "message" => "Error",
                    "data" => $validator->messages()
                ];

                return response()->json($responseArray);
            }
        }
    }

    //PRODUCT IMAGE DATA DELETE FUNCTION
    function adminApiPostDeleteProductImageDetails(Request $request)
    {

        $rules = [
            'id' => 'required|exists:product_variant_images,id',
        ];

        $errorMessages = array(
            'id.exists' => 'Image ID does not exists'
        );

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            $category = ProductVariantImagesModel::where('id', $request->id)
                ->delete();

            if ($category) {

                $responseArray = [
                    "status" => true,
                    "message" => "Image Deleted Successfully!",
                    "data" => []
                ];

                return response()->json($responseArray);
            } else {

                $responseArray = [
                    "status" => false,
                    "message" => "Unable to perform this action!",
                    "data" => [
                        "error" => ["Requested Image Not Found"]
                    ]
                ];

                return response()->json($responseArray);
            }
        }
    }

    // PRODUCT BROCHURE OR INTRO VIDEO DELETE FUNCTION
    function adminApiPostDeleteProductBrochureOrIntroVideoDetails(Request $request)
    {

        $rules = [
            'product_unique' => 'required|exists:product,product_unique',
            'item_to_delete' => 'required|in:brochure,intro_video',
        ];

        $errorMessages = [
            "product_unique.exists" => "Requested Product does not exists",
            "item_to_delete.in" => "The selected item to delete is invalid, please select from brochure and intro_video",
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if ($validator->fails()) {

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => $validator->messages()
            ];

            return response()->json($responseArray);
        } else {

            // delete product brochure

            if ($request->item_to_delete == 'brochure') {

                $product_brochure_data = ProductVariantBrochuresModel::select('*')
                    ->where(['product_unique' => $request->product_unique])
                    ->first();

                if ($product_brochure_data) {

                    $filePath = public_path(PRODUCT_BROCHURE_PDFS . '/' . $product_brochure_data->brochure);

                    if (File::exists($filePath)) {

                        // Delete the file
                        if (File::delete($filePath)) {
                            $fileDeleted = true;
                        } else {
                            $fileDeleted = false;
                        }
                    } else {
                        $fileDeleted = true; // File does not exist, consider it as deleted
                    }

                    $productBrochureDelete = ProductVariantBrochuresModel::where('product_unique', $request->product_unique)->delete();

                    if ($productBrochureDelete && $fileDeleted) {

                        $responseArray = [
                            "status" => true,
                            "message" => 'Brochure and file deleted successfully',
                            "data" => []
                        ];

                        return response()->json($responseArray);
                    } else {

                        $responseArray = [
                            "status" => false,
                            "message" => 'Failed to delete brochure or file',
                            "data" => [
                                "error" => ['Failed to delete brochure or file']
                            ]
                        ];

                        return response()->json($responseArray);
                    }
                } else {

                    $responseArray = [
                        "status" => false,
                        "message" => 'Brochure not found',
                        "data" => [
                            "error" => ['Brochure not found']
                        ]
                    ];

                    return response()->json($responseArray);
                }
            }


            // delete product intro video

            if ($request->item_to_delete == 'intro_video') {

                $product_intro_video_data = ProductVariantVideosModel::select('*')
                    ->where(['product_unique' => $request->product_unique, 'video_type' => 'INTRO'])
                    ->first();

                if ($product_intro_video_data) {

                    $filePath = public_path(PRODUCT_VIDEOS . '/' . $product_intro_video_data->video);

                    if (File::exists($filePath)) {

                        // Delete the file
                        if (File::delete($filePath)) {
                            $fileDeleted = true;
                        } else {
                            $fileDeleted = false;
                        }
                    } else {
                        $fileDeleted = true; // File does not exist, consider it as deleted
                    }

                    $productIntroVideoDelete = ProductVariantVideosModel::where(['product_unique' => $request->product_unique, 'video_type' => 'INTRO'])->delete();

                    if ($productIntroVideoDelete && $fileDeleted) {

                        $responseArray = [
                            "status" => true,
                            "message" => 'Intro video and file deleted successfully',
                            "data" => []
                        ];

                        return response()->json($responseArray);
                    } else {

                        $responseArray = [
                            "status" => false,
                            "message" => 'Failed to delete intro video or file',
                            "data" => [
                                "error" => ['Failed to delete intro video or file']
                            ]
                        ];

                        return response()->json($responseArray);
                    }
                } else {

                    $responseArray = [
                        "status" => false,
                        "message" => 'Intro video not found',
                        "data" => [
                            "error" => ['Intro video not found']
                        ]
                    ];

                    return response()->json($responseArray);
                }
            }

            $responseArray = [
                "status" => false,
                "message" => "Unable to perform this action!",
                "data" => [
                    "error" => ["Something went wrong..!"]
                ]
            ];

            return response()->json($responseArray);
        }
    }
}
