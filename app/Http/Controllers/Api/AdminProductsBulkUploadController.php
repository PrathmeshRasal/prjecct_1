<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategoryModel;
use App\Models\ProductSpecsKeyValueModel;
use App\Models\ProductSpecsModel;
use App\Models\ProductTagModel;
use App\Models\ProductVariantBrochuresModel;
use App\Models\ProductVariantHighlightsModel;
use App\Models\ProductVariantImagesModel;
use App\Models\ProductVariantVideosModel;
use App\Models\TagModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;


use App\Imports\ColumnImport;
use App\Models\ProductVariantModel;
use App\Models\ProductVariantSizeModel;
use Maatwebsite\Excel\Facades\Excel;

require_once app_path('Helpers/Constants.php');

class AdminProductsBulkUploadController extends Controller
{
    public function apiPostProductsBulkUpload(Request $request)
    {
        $rules = [
            'products_file' => 'required|file|mimes:xlsx,xls',
            'products_media_zip' => 'required|mimes:zip|max:256000' // Adjust the file size limit to 250MB (250MB * 1024 = 256000 KB)
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

            try {

                // HANDELLING ZIP FILE STARTS HERE

                $zipFile = $request->file('products_media_zip');

                // Validate and extract the zip file
                if ($zipFile->isValid()) {

                    $zipPath = $zipFile->storeAs('temp', 'temp.zip'); // Store in the 'temp' directory
                    $extractPath = storage_path('app/temp');

                    $zip = new \ZipArchive;
                    $res = $zip->open(storage_path("app/{$zipPath}"));

                    if ($res === TRUE) {

                        $zip->extractTo($extractPath);
                        $zip->close();

                        // Further processing or moving files to the desired location

                    }

                    // Clean up: delete the temporary zip file
                    Storage::delete($zipPath);

                    if (!$res) {

                        // Handle zip extraction failure
                        $responseArray = [
                            "status" => false,
                            "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                            "data" => 'Unable to extract zip file'
                        ];

                        return response()->json($responseArray);
                    }
                } else {

                    // Handle invalid file
                    $responseArray = [
                        "status" => false,
                        "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                        "data" => 'Please provide valid zip file'
                    ];

                    return response()->json($responseArray);
                }


                // HANDELLING ZIP FILE ENDS HERE



                // ------------------------------------------------------------



                // HANDELLING EXCEL SHEET READING STARTS HERE

                $import = new ColumnImport;
                Excel::import($import, $request->products_file);

                // Get the transposed data
                $transposedData = $import->getTransposedData();

                if ($transposedData->count() < 2) {

                    $responseArray = [
                        "status" => false,
                        "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                        "data" => ['error' => 'Please provide valid sheet']
                    ];

                    return response()->json($responseArray, 400);
                }

                $headers = $transposedData->splice(0, 1)->first();

                $allProducts = Product::all()->pluck('product_name')->map(function ($productName) {
                    return strtolower($productName);
                });

                $categoryIdsHierarchy = getCategoryIdsHierarchy();

                $allTags = TagModel::select('id', 'title')->get()->toArray();

                $tagsTitleToIdMapped = [];

                foreach ($allTags as $tag) {
                    $tagsTitleToIdMapped[strtolower($tag['title'])] = $tag['id'];
                }

                $parentCategoryIds = array_keys($categoryIdsHierarchy);

                $allProductsData = [];

                $allErrorsData = [];

                foreach ($transposedData as $productIndex => $product) {

                    $productData = [];
                    $errors = [];

                    $highlightsFound = false;
                    $specificationKeysFound = false;
                    $tagsFound = false;
                    $atLeastOneTagInserted = false;
                    $productName = '';
                    $currentSpecificationHeader = '';
                    $imagesFound = false;
                    $brochureFound = false;
                    $introVideoFound = false;
                    $machineSizeFound = false;

                    foreach ($product as $index => $value) {

                        $safeValue = trim(htmlspecialchars($value));

                        $validated = validator(['input' => $safeValue], [
                            'input' => 'nullable|string',
                        ])->passes();

                        if (!$validated) {

                            $errors = addError($errors, $productIndex, $headers->get($index), $value);
                            break;
                        }

                        if ($index === 0) {

                            if (empty($safeValue) && $safeValue != 0) {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $productData['company_name'] = $safeValue;
                            }
                        } elseif ($index === 1) {

                            if (empty($safeValue) && $safeValue != 0) {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $productData['country'] = $safeValue;
                            }
                        } elseif ($index === 2) {

                            if (empty($safeValue) && $safeValue != 0) {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $productData['product_type'] = $safeValue;
                            }
                        } elseif ($index === 3) {

                            if (empty($safeValue) && $safeValue != 0) {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $productName = trim($productData['company_name']) . '-' . trim($safeValue);

                                // if ($allProducts->contains(strtolower($productName))) {
                                //     $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Unable to create product name: Product already exists with company ' . $productData['company_name'] . ' and model' . $safeValue);
                                //     break;
                                // }

                                $productData['product_model'] = $safeValue;
                                $productData['product_name'] = $productName;
                            }
                        } elseif ($index === 4) {

                            if (empty($safeValue) && $safeValue != 0) {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $productData['industry_name'] = $safeValue;
                            }
                        } elseif ($index === 5) {

                            if (empty($safeValue) && $safeValue != '0') {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                if (!in_array($safeValue, $parentCategoryIds)) {
                                    $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Provided head category id does not exist');
                                    break;
                                }

                                $productData['head_category'] = $safeValue;
                            }
                        } elseif ($index === 6) {

                            if (empty($safeValue) && $safeValue != '0') {

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide input for this field');
                                break;
                            } else {

                                $headCategory = $productData['head_category'];

                                if (!in_array($safeValue, $categoryIdsHierarchy[$headCategory])) {

                                    $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Provided sub category id does not belongs to head category');
                                    break;
                                }

                                $productData['sub_category'] = $safeValue;
                            }
                        }

                        if (strtolower($headers->get($index)) == 'tags') {

                            $tagsFound = true;
                            continue;
                        }

                        if ($tagsFound) {

                            if (!$headers->get($index) && !$atLeastOneTagInserted) {

                                $tagsFound = false;

                                $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide at lease one tag for a product');
                                break;
                            } else {

                                if ($value) {

                                    if (!isset($tagsTitleToIdMapped[strtolower($safeValue)])) {

                                        $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide valid existing tag');

                                        break;
                                    }

                                    $tagId = $tagsTitleToIdMapped[strtolower($safeValue)];

                                    if (!in_array($tagId, $productData['tags'] ?? [])) {
                                        $productData['tags'][] = $tagId;
                                    }

                                    $atLeastOneTagInserted = true;
                                } else {

                                    if ($atLeastOneTagInserted) {

                                        $tagsFound = false;
                                        continue;
                                    } else {

                                        $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Please provide at lease one tag for a product');

                                        break;
                                    }
                                }
                            }
                        }



                        if (strtolower($headers->get($index)) == 'product images') {
                            $imagesFound = true;
                            continue;
                        }

                        if ($imagesFound) {

                            if (!$headers->get($index)) {

                                $imagesFound = false;
                                continue;
                            } else {

                                if ($value) {

                                    // Check if a specific file exists in the extracted directory
                                    $filePath = $extractPath . DIRECTORY_SEPARATOR . $safeValue;

                                    if (!File::exists($filePath)) {

                                        $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Provided file does not exists');
                                        continue;
                                    }

                                    $productData['images'][] = $safeValue;
                                }
                            }
                        }



                        if (strtolower($headers->get($index)) == 'product brochure') {


                            $brochureFound = true;
                            continue;
                        }

                        if ($brochureFound) {

                            if (!$headers->get($index)) {

                                $brochureFound = false;
                                continue;
                            } else {

                                if ($value) {

                                    // Check if a specific file exists in the extracted directory
                                    $filePath = $extractPath . DIRECTORY_SEPARATOR . $safeValue;

                                    if (!File::exists($filePath)) {

                                        $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Provided file does not exists');
                                        continue;
                                    }

                                    $productData['brochure'][] = $safeValue;
                                }
                            }
                        }



                        if (strtolower($headers->get($index)) == 'intro video') {


                            $introVideoFound = true;
                            continue;
                        }

                        if ($introVideoFound) {

                            if (!$headers->get($index)) {

                                $introVideoFound = false;
                                continue;
                            } else {

                                if ($value) {

                                    // Check if a specific file exists in the extracted directory
                                    $filePath = $extractPath . DIRECTORY_SEPARATOR . $safeValue;

                                    if (!File::exists($filePath)) {

                                        $errors = addError($errors, $productIndex, $headers->get($index), $value, 'Provided file does not exists');
                                        continue;
                                    }

                                    $productData['intro_video'][] = $safeValue;
                                }
                            }
                        }



                        if (strtolower($headers->get($index)) == 'highlights') {
                            $highlightsFound = true;
                            continue;
                        }

                        if ($highlightsFound) {
                            if (!$headers->get($index)) {
                                $highlightsFound = false;
                                continue;
                            } else {
                                if ($value) {
                                    $productData['highlights'][] = $safeValue;
                                }
                            }
                        }

                        if (strtolower($headers->get($index)) == 'machine size') {
                            $machineSizeFound = true;
                            continue;
                        }

                        if ($machineSizeFound) {
                            if (!$headers->get($index)) {
                                $machineSizeFound = false;
                                continue;
                            } else {
                                if ($value) {
                                    $header = trim(strtolower($headers->get($index)));
                                    switch ($header) {
                                        case 'length in inch':
                                            $productData['machine_size_details']['length_in_inch'] = $safeValue;
                                            break;

                                        case 'length in mm':
                                            $productData['machine_size_details']['length_in_mm'] = $safeValue;
                                            break;

                                        case 'width in inch':
                                            $productData['machine_size_details']['width_in_inch'] = $safeValue;
                                            break;

                                        case 'width in mm':
                                            $productData['machine_size_details']['width_in_mm'] = $safeValue;
                                            break;

                                        case 'height in inch':
                                            $productData['machine_size_details']['height_in_inch'] = $safeValue;
                                            break;

                                        case 'height in mm':
                                            $productData['machine_size_details']['height_in_mm'] = $safeValue;
                                            break;

                                        default:
                                            $productData['machine_size_details']['machine_weight'] = $safeValue;
                                            break;
                                    }
                                }
                            }
                        }

                        if (strtolower($headers->get($index)) == 'product specifications') {

                            $specificationKeysFound = true;
                            continue;
                        }

                        if ($specificationKeysFound) {

                            if ($headers->get($index) && !empty(trim($headers->get($index))) && empty($currentSpecificationHeader)) {

                                $currentSpecificationHeader = trim($headers->get($index));
                                continue;
                            } elseif ($headers->get($index) && !empty(trim($headers->get($index))) && !empty($currentSpecificationHeader)) {

                                if (!empty(trim($safeValue)) || $safeValue == '0') {

                                    $productData['specification_keys'][$currentSpecificationHeader][trim($headers->get($index))] = $safeValue;
                                }
                            } else {

                                $currentSpecificationHeader = '';
                            }
                        }
                    }

                    if (!empty($errors)) {

                        $allErrorsData[$productIndex + 1] = $errors;
                    } else {

                        $allProductsData[] = $productData;
                    }
                }


                // HANDELLING EXCEL SHEET READING ENDS HERE



                // ------------------------------------------------------------



                // SAVING THE PRODUCTS, COPYING THE FILES AND DELETING THE EXTRACTED FILES STARTS HERE



                foreach ($allProductsData as $product) {

                    $category = Category::where('category_id', $product['sub_category'])->first();

                    // PRODUCT CODE

                    if ($product['head_category'] == 15 || $product['head_category'] == 16) {

                        $product['product_name'] = trim($product['product_type']) . ' - ' . trim($product['product_model']);

                        $existingProduct = Product::where('product_name', $product['product_name'])->first();
                    } else {
                        $existingProduct = Product::where('product_name', $product['product_name'])->first();
                    }

                    if ($existingProduct) {

                        $productDetails = $existingProduct;
                        $product_unique = $productDetails->product_unique;
                    } else {

                        // this value should be unique
                        $minValue = MIN_PRODUCT_INT_VALUE;
                        $maxValue = MAX_PRODUCT_INT_VALUE;
                        $product_unique = random_int($minValue, $maxValue);

                        // PRODUCT DATA ADD CODE
                        $productDetails = new Product;
                        $productDetails->product_name = $product['product_name'];
                        $productDetails->is_active = 1;
                        $productDetails->product_unique = $product_unique;
                        $productDetails->save();
                    }



                    // PRODUCT VARIANT CODE

                    if ($existingProduct) {

                        $existingProductVariant = ProductVariantModel::where('product_unique', $product_unique)->first();

                        $productVariant = $existingProductVariant;
                    } else {

                        // PRODUCT VARIANT DATA CODE ADD

                        $productVariant = new ProductVariantModel;
                        $productVariant->product_unique = $product_unique;
                    }

                    $productVariant->machine_type = $category->category_name;
                    $productVariant->company_name = $product['company_name'];
                    $productVariant->country = $product['country'];
                    $productVariant->product_type = $product['product_type'];
                    $productVariant->product_model = $product['product_model'];
                    $productVariant->industry_name = $product['industry_name'];
                    $productVariant->save();


                    // PRODUCT SIZE CODE

                    if ($existingProduct) {

                        $existingProductSize = ProductVariantSizeModel::where('product_unique', $product_unique)->first();

                        $productSize = $existingProductSize;

                        $productSize->product_height_inch = $product['machine_size_details']['height_in_inch'] ?? $productSize->product_height_inch;
                        $productSize->product_height_mm = $product['machine_size_details']['height_in_mm'] ?? $productSize->product_height_mm;
                        $productSize->product_width_inch = $product['machine_size_details']['width_in_inch'] ?? $productSize->product_width_inch;
                        $productSize->product_width_mm = $product['machine_size_details']['width_in_mm'] ?? $productSize->product_width_mm;
                        $productSize->product_length_inch = $product['machine_size_details']['length_in_inch'] ?? $productSize->product_length_inch;
                        $productSize->product_length_mm = $product['machine_size_details']['length_in_mm'] ?? $productSize->product_length_mm;
                        $productSize->product_weight = $product['machine_size_details']['machine_weight'] ?? $productSize->product_weight;
                        $productSize->save();
                    } else {

                        // PRODUCT SIZE DATA CODE ADD

                        $productSize = new ProductVariantSizeModel;
                        $productSize->product_unique = $product_unique;
                        $productSize->product_height_inch = $product['machine_size_details']['height_in_inch'] ?? '';
                        $productSize->product_height_mm = $product['machine_size_details']['height_in_mm'] ?? '';
                        $productSize->product_width_inch = $product['machine_size_details']['width_in_inch'] ?? '';
                        $productSize->product_width_mm = $product['machine_size_details']['width_in_mm'] ?? '';
                        $productSize->product_length_inch = $product['machine_size_details']['length_in_inch'] ?? '';
                        $productSize->product_length_mm = $product['machine_size_details']['length_in_mm'] ?? '';
                        $productSize->product_weight = $product['machine_size_details']['machine_weight'] ?? '';
                        $productSize->save();
                    }


                    // PRODUCT IMAGE DATA CODE ADD

                    if (!empty($product['images'] ?? [])) {

                        for ($i = 0; $i < count($product['images']); $i++) {

                            // create file name

                            $productNameSlug = Str::slug($product['product_name'] . '-' . $product_unique, '-');

                            $extension = pathinfo($product['images'][$i], PATHINFO_EXTENSION);

                            // create final file name
                            $fileName = $productNameSlug . '-' . time() . '_' . $i . '.' . $extension;

                            // copy image to right folder

                            $sourcePath = $extractPath . DIRECTORY_SEPARATOR . $product['images'][$i];

                            $destinationPath = IMAGE_PATH_PRODUCT_IMAGES . DIRECTORY_SEPARATOR . $fileName;


                            $destinationDirectory = dirname($destinationPath);

                            File::makeDirectory($destinationDirectory, 0755, true, true);

                            File::copy($sourcePath, $destinationPath);

                            // save details in the database
                            $productVariantImage = new ProductVariantImagesModel;
                            $productVariantImage->product_unique = $product_unique;
                            $productVariantImage->product_variant_id = $productVariant->id;
                            $productVariantImage->image = $fileName;
                            $productVariantImage->save();
                        }
                    } elseif (!$existingProduct) {

                        // admin didn't send any image
                        $productVariantImage = new ProductVariantImagesModel;
                        $productVariantImage->product_unique = $product_unique;
                        $productVariantImage->product_variant_id = $productVariant->id;
                        $productVariantImage->image = DEFAULT_PRODUCT_IMAGE;
                        $productVariantImage->save();
                    }


                    // PRODUCT BROCHURE DATA CODE ADD

                    if ($existingProduct && !empty($product['brochure'] ?? [])) {

                        // if brochure already exists for existing product delete it

                        $product_brochure_data = ProductVariantBrochuresModel::select('*')
                            ->where(['product_unique' => $product_unique])
                            ->first();

                        if ($product_brochure_data) {

                            $filePath = public_path(PRODUCT_BROCHURE_PDFS . '/' . $product_brochure_data->brochure);

                            if (File::exists($filePath)) {

                                // Delete the file
                                File::delete($filePath);
                            }

                            ProductVariantBrochuresModel::where('product_unique', $product_unique)->delete();
                        }
                    } elseif (!empty($product['brochure'] ?? [])) {

                        // create file name

                        $productNameSlug = Str::slug($product['product_name'] . '-' . $product_unique, '-');

                        $extension = pathinfo($product['brochure'][0], PATHINFO_EXTENSION);

                        // create final file name

                        $fileName = $productNameSlug . '-' . 'brochure' . '-' . time() . '.' . $extension;

                        // copy pdf to right folder

                        $sourcePath = $extractPath . DIRECTORY_SEPARATOR . $product['brochure'][0];

                        $destinationPath = PRODUCT_BROCHURE_PDFS . DIRECTORY_SEPARATOR . $fileName;

                        $destinationDirectory = dirname($destinationPath);

                        File::makeDirectory($destinationDirectory, 0755, true, true);

                        File::copy($sourcePath, $destinationPath);

                        // save details in the database
                        $productVariantBrochure = new ProductVariantBrochuresModel;
                        $productVariantBrochure->product_unique = $product_unique;
                        $productVariantBrochure->product_variant_id = $productVariant->id;
                        $productVariantBrochure->brochure = $fileName;
                        $productVariantBrochure->save();
                    }


                    // PRODUCT INTRO VIDEO DATA CODE ADD


                    if ($existingProduct && !empty($product['intro_video'] ?? [])) {

                        // if product video already exists for existing product delete it

                        $product_intro_video_data = ProductVariantVideosModel::select('*')
                            ->where(['product_unique' => $product_unique, 'video_type' => 'INTRO'])
                            ->first();

                        if ($product_intro_video_data) {

                            $filePath = public_path(PRODUCT_VIDEOS . '/' . $product_intro_video_data->video);

                            if (File::exists($filePath)) {

                                // Delete the file
                                File::delete($filePath);

                                ProductVariantVideosModel::where(['product_unique' => $product_unique, 'video_type' => 'INTRO'])->delete();
                            }
                        }
                    } elseif (!empty($product['intro_video'] ?? [])) {

                        // create file name

                        $productNameSlug = Str::slug($product['product_name'] . '-' . $product_unique, '-');

                        $extension = pathinfo($product['intro_video'][0], PATHINFO_EXTENSION);

                        // create final file name

                        $fileName = $productNameSlug . '-' . 'intro-video' . '-' . time() . '.' . $extension;

                        // copy video to right folder

                        $sourcePath = $extractPath . DIRECTORY_SEPARATOR . $product['intro_video'][0];

                        $destinationPath = PRODUCT_VIDEOS . DIRECTORY_SEPARATOR . $fileName;


                        $destinationDirectory = dirname($destinationPath);

                        File::makeDirectory($destinationDirectory, 0755, true, true);

                        File::copy($sourcePath, $destinationPath);

                        // save details in the database
                        $productVariantIntroVideo = new ProductVariantVideosModel;
                        $productVariantIntroVideo->product_unique = $product_unique;
                        $productVariantIntroVideo->product_variant_id = $productVariant->id;
                        $productVariantIntroVideo->video = $fileName;
                        $productVariantIntroVideo->video_type = 'INTRO';
                        $productVariantIntroVideo->save();
                    }


                    //PRODUCT HIGHLIGHT DATA CODE ADD

                    foreach ($product['highlights'] ?? [] as $highlight) {

                        $productVariantHighlight = new ProductVariantHighlightsModel;
                        $productVariantHighlight->product_unique = $product_unique;
                        $productVariantHighlight->product_variant_id = $productVariant->id;
                        $productVariantHighlight->highlight_text = $highlight;
                        $productVariantHighlight->save();
                    }

                    // NOW PRODUCT SAVING CATEGORIES BELOW

                    $productCategories = [$product['head_category'], $product['sub_category']];

                    if ($existingProduct) {

                        ProductCategoryModel::where('product_unique', $product_unique)
                            ->delete();
                    }

                    foreach ($productCategories as $category) {

                        $productCategory = new ProductCategoryModel;
                        $productCategory->product_unique = $product_unique;
                        $productCategory->category = $category;
                        $productCategory->save();
                    }

                    // PRODUCT SPACIFICATION DATA ADD CODE

                    if (!empty($product['specification_keys'] ?? [])) {

                        // Now you can work with the PHP array
                        foreach ($product['specification_keys'] as $head_key => $sub_key_values) {

                            if ($existingProduct) {

                                $existingProductSpek =  ProductSpecsModel::where([
                                    'product_unique' => $product_unique,
                                    'product_variant_id' => $productVariant->id,
                                    'specification_heading' => $head_key
                                ])->first();

                                if ($existingProductSpek) {

                                    $productSpek =  $existingProductSpek;
                                } else {
                                    $productSpek = new ProductSpecsModel;
                                    $productSpek->product_unique = $product_unique;
                                    $productSpek->product_variant_id = $productVariant->id;
                                    $productSpek->specification_heading = $head_key;
                                    $productSpek->is_active = IS_ACTIVE_YES;
                                    $productSpek->save();
                                }
                            } else {
                                $productSpek = new ProductSpecsModel;
                                $productSpek->product_unique = $product_unique;
                                $productSpek->product_variant_id = $productVariant->id;
                                $productSpek->specification_heading = $head_key;
                                $productSpek->is_active = IS_ACTIVE_YES;
                                $productSpek->save();
                            }

                            foreach ($sub_key_values as $subItemKey => $subItemValue) {

                                if ($existingProduct) {

                                    $existingProductSpekValues =  ProductSpecsKeyValueModel::where([
                                        'product_unique' => $product_unique,
                                        'product_variant_id' => $productVariant->id,
                                        'product_spec_id' => $productSpek->id,
                                        'spec_key' => $subItemKey
                                    ])->first();

                                    if ($existingProductSpekValues) {

                                        $productSpekValues =  $existingProductSpekValues;
                                        $productSpekValues->spec_value = $subItemValue;
                                        $productSpekValues->save();
                                    } else {
                                        $productSpekValues = new ProductSpecsKeyValueModel;
                                        $productSpekValues->product_unique = $product_unique;
                                        $productSpekValues->product_variant_id = $productVariant->id;
                                        $productSpekValues->product_spec_id = $productSpek->id;
                                        $productSpekValues->spec_key = $subItemKey;
                                        $productSpekValues->spec_value = $subItemValue;
                                        $productSpekValues->is_active = IS_ACTIVE_YES;
                                        $productSpekValues->save();
                                    }
                                } else {
                                    $productSpekValues = new ProductSpecsKeyValueModel;
                                    $productSpekValues->product_unique = $product_unique;
                                    $productSpekValues->product_variant_id = $productVariant->id;
                                    $productSpekValues->product_spec_id = $productSpek->id;
                                    $productSpekValues->spec_key = $subItemKey;
                                    $productSpekValues->spec_value = $subItemValue;
                                    $productSpekValues->is_active = IS_ACTIVE_YES;
                                    $productSpekValues->save();
                                }
                            }
                        }
                    }

                    // PRODUCT TAGS DATA ADD CODE

                    foreach ($product['tags'] as $product_tag) {

                        $existingTag = ProductTagModel::where([
                            'product_unique' => $product_unique,
                            'tag_id' => $product_tag
                        ])->first();

                        if (!$existingTag) {

                            $tag = new ProductTagModel;
                            $tag->product_unique = $product_unique;
                            $tag->tag_id = $product_tag;
                            $tag->save();
                        }
                    }
                }

                // Delete all files in the 'temp' directory
                deleteFilesInDirectory($extractPath);

                if (!empty($allErrorsData)) {

                    $responseArray = [
                        "status" => false,
                        "message" => "Bulk upload was unable to upload all products!",
                        "data" => $allErrorsData
                    ];
                } else {

                    $responseArray = [
                        "status" => true,
                        "message" => "Bulk upload successfully completed!",
                        "data" => []
                    ];
                }


                return response()->json($responseArray);
            } catch (\Exception $e) {

                $responseArray = [
                    "status" => false,
                    "message" => ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION,
                    "data" => ['error' => $e->getMessage()]
                ];

                return response()->json($responseArray, ($e->getCode() ? ($e->getCode() != 0 ? $e->getCode() : 400) : 400));
            }
        }
    }
}


// Function to fetch and organize category IDs into a hierarchy
function getCategoryIdsHierarchy()
{
    // Fetch all categories
    $categories = Category::all();

    // Organize category IDs into parent and child arrays
    $categoryIdsHierarchy = [];

    foreach ($categories as $category) {
        $categoryId = $category->category_id;

        if ($category->category_parent_id === 0) {
            // Parent category
            $categoryIdsHierarchy[$categoryId] = [];
        } else {
            // Child category
            $parentCategoryId = $category->category_parent_id;
            $categoryIdsHierarchy[$parentCategoryId][] = $categoryId;
        }
    }

    return $categoryIdsHierarchy;
}

function addError($errors, $productIndex, $header, $value, $message = 'Please provide input for this field')
{
    $errors[] = [
        'product_column_index' => $productIndex + 1,
        'error_at' => $header,
        'error_value' => $value,
        'message' => $message
    ];

    return $errors;
}

function deleteFilesInDirectory($directory)
{
    if (File::isDirectory($directory)) {
        $files = File::allFiles($directory);

        foreach ($files as $file) {
            // Use File::delete to delete files
            File::delete($file->getPathname());
        }
    }
}
