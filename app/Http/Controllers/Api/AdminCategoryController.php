<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use App\Rules\ValidatedIsActiveFieldRule;
use Illuminate\Support\Str;

require_once app_path('Helpers/Constants.php');

class AdminCategoryController extends Controller
{
    //ALL PARENT CATEGORY DATA GET FUNCTION
    function apiGetViewAllCategories(Request $request) {

        $categories = Category::select('category_id', 'category_name', 'category_parent_id', 'is_active', 'image')
            ->orderBy('category_parent_id', 'asc')
            ->get();

        $groupedCategories = [];

        foreach($categories as $category) {

            $parentID = $category->category_parent_id;

            if (!isset($groupedCategories[$parentID])) {
                $groupedCategories[$parentID] = [];
            }

            $groupedCategories[$parentID][] = [
                'category_id'         => $category->category_id,
                'category_name'       => $category->category_name,
                'category_parent_id'  => $category->category_parent_id,
                'is_active'           => $category->is_active,
                'image'               => asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $category->image),
            ];
        }

        $output = [];

        foreach($groupedCategories[0] ?? [] as $parentCategory) {

            $output[] = $parentCategory;

            if (isset($groupedCategories[$parentCategory['category_id']])) {
                $output = array_merge($output, $groupedCategories[$parentCategory['category_id']]);
            }
        }

        if(!empty($output)) {

            $responseArray = [
                "status"    =>  true,
                "message"   =>  "List of Categories found",
                "data"      =>  [
                    "category_list" =>  $output
                ]
            ];

            return response()->json($responseArray);

        } else {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  [
                    "error" =>  ["Categories not found"]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    //CATEGORY DATA ADD FUNCTION
    public function apiPostProductCategoryDetails(Request $request) {

        $rules = [
            'category_name'       => 'required|string|max:100',
            'image'               => 'file|mimes:webp,jpg,jpeg,png|max:4096',
            'category_parent_id'  => 'required|numeric',
        ];

        $rules['category_name'] .= '|unique:categories,category_name,NULL,category_id,category_parent_id,' . $request->category_parent_id;

        $errorMessages = [
            'category_name.unique' => 'Category Name Already Taken for this Parent Category',
        ];

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails())
        {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $parentCategoryId = 0;

            if($request->category_parent_id != 0) {

                // adding child categories
                $existingCategory = Category::select('category_id')
                    ->where('category_id', $request->category_parent_id)
                    ->first();

                if($existingCategory) {

                    // category exists
                    $parentCategoryId = $existingCategory->category_id;

                } else {

                    $responseArray = [
                        "status"    =>  false,
                        "message"   =>  "Unable to perform this action!",
                        "data"      =>  [
                            "error" =>  ["Parent category does not exists"]
                        ]
                    ];

                    return response()->json($responseArray);

                }

            }

            $fileName = DEFAULT_CATEGORY_IMAGE;

            if($request->image != null) {
                // create slug name from title
                $parentCategoryTitleSlug = Str::slug($request->category_name, '-');

                // file name
                $fileName = $parentCategoryTitleSlug . '-' . time() . '.' . $request->image->extension();

                // upload image
                $request->image->move(IMAGE_PATH_PARENT_CATEGORY_IMAGES, $fileName);
            }

            $pCategory = new Category();
            $pCategory->category_name = $request->category_name;
            $pCategory->category_parent_id = $parentCategoryId;
            $pCategory->image = $fileName;
            $pCategory->is_active = "1";
            $pCategory->save();

            $responseArray = [
                "status"    =>  true,
                "message"   =>  "Product Category Added Successfully!",
                "data"      =>  []
            ];

            return response()->json($responseArray);

        }
    }

    //CATEGORY SINGLE ROW GET DATA FUNCTION
    public function apiPostViewSingleCategory(Request $request) {

        $rules = [
            'category_id'           =>  'integer|exists:categories,category_id',
            'category_parent_id'    =>  'integer'
        ];

        $errorMessages = array(
            'category_id.exists' =>  'Category does not exists'
        );

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails())
        {
            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $categories = [];

            if($request->category_id) {

                $categories = Category::select('category_id', 'category_name', 'category_parent_id', 'is_active', 'image')
                    ->where('category_id', $request->category_id)
                    ->paginate(25)
                    ->toArray();

            } else if($request->category_parent_id == 0) {
                // list all categories who are parent
                $categories = Category::select('category_id', 'category_name', 'category_parent_id', 'is_active', 'image')
                    ->where('category_parent_id', 0)
                    ->paginate(25)
                    ->toArray();

            } else if($request->category_parent_id > 0) {

                // list all child categories on behalf of parent id
                $categories = Category::select('category_id', 'category_name', 'category_parent_id', 'is_active', 'image')
                    ->where('category_parent_id', $request->category_parent_id)
                    ->paginate(25)
                    ->toArray();

            }

            if(empty($categories)) {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Categories not found",
                    "data"      =>  []
                ];

                return response()->json($responseArray);

            } else if($categories['total'] > 0) {

                foreach ($categories['data'] as &$category) {

                    $category['image'] = asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $category['image']);

                }

                $categoryList['category_list'] = $categories['data'];
                $categoryList['current_page'] = $categories['current_page'];
                $categoryList['per_page'] = $categories['per_page'];
                $categoryList['total'] = $categories['total'];
                $categoryList['last_page'] = $categories['last_page'];

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "List of Categories found",
                    "data"      =>  $categoryList
                ];

                return response()->json($responseArray);

            } else {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Categories not found",
                    "data"      =>  []
                ];

                return response()->json($responseArray);
            }
        }
    }

    //CATEGORY DATA UPDATE FUNCTION
    function apiPostUpdateProductCategory(Request $request) {

        $rules = [
            'category_name'         =>  'string|max:100',
            'image'                 =>  'file|mimes:webp,jpg,jpeg,png|max:4096', // 4096 = 4 mb
            'is_active'             =>  ['numeric', new ValidatedIsActiveFieldRule],
            'category_parent_id'    =>  'integer',
            'category_id'           =>  'required|exists:categories,category_id',
        ];

        $errorMessages = array(
            'category_name.unique'      =>  'Category name already taken',
            'category_id.exists'   =>  'Category does not exists'
        );

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "There is error while filling the form",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            // check if category name is duplicate

            $duplicateCategoryName = Category::where('category_name', $request->category_name)
                ->where('category_id', '!=', $request->category_id)
                ->first();

            if(!$duplicateCategoryName) {

                // update category
                $parentCategoryId = 0;
                if($request->category_parent_id != 0) {
                    // adding child categories
                    $existingCategory = Category::select('category_id')
                        ->where('category_id', $request->category_parent_id)
                        ->first();

                    if($existingCategory) {
                        // category exists
                        $parentCategoryId = $existingCategory->category_id;

                    } else {

                        $responseArray = [
                            "status"    =>  false,
                            "message"   =>  "Unable to perform this action!",
                            "data"      =>  [
                                "error" =>  ["Parent category does not exists"]
                            ]
                        ];

                        return response()->json($responseArray);

                    }
                }

                $fileName = null;

                if($request->image != null) {
                    // create slug name from title
                    $parentCategoryTitleSlug = Str::slug($request->category_name ?: "category-image", '-');

                    // file name
                    $fileName = $parentCategoryTitleSlug . '-' . time() . '.' . $request->image->extension();

                    // upload image
                    $request->image->move(IMAGE_PATH_PARENT_CATEGORY_IMAGES, $fileName);
                }

                $updateCategory = [];

                if($request->category_name)
                {
                    $updateCategory['category_name'] = $request->category_name;
                }
                if(isset($request->category_parent_id))
                {
                    $updateCategory['category_parent_id'] = $request->category_parent_id;
                }
                if($request->image)
                {
                    $updateCategory['image'] = $fileName;
                }
                if(isset($request->is_active))
                {
                    $updateCategory['is_active'] = $request->is_active;
                }

                if(!empty($updateCategory)) {

                    $category = Category::where('category_id', $request->category_id)
                        ->update($updateCategory);

                    if($category) {
                        $responseArray = [
                            "status"    =>  true,
                            "message"   =>  "Category Updated Successfully!",
                            "data"      =>  []
                        ];

                        return response()->json($responseArray);

                    } else {

                        $responseArray = [
                            "status"    =>  false,
                            "message"   =>  "Unable to perform this action!",
                            "data"      =>  [
                                "error" =>  ["Unable to update category"]
                            ]
                        ];

                        return response()->json($responseArray);

                    }

                } else {

                    $responseArray = [
                        "status"    =>  false,
                        "message"   =>  "Unable to perform this action!",
                        "data"      =>  [
                            "error" =>  ["Unable to update category"]
                        ]
                    ];

                    return response()->json($responseArray);

                }

            } else {
                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Unable to perform this action!",
                    "data"      =>  [
                        "error" =>  ["Category name already exists"]
                    ]
                ];

                return response()->json($responseArray);
            }

        }
    }

    //CATEGORY DATA SOFT DELETE FUNCTION
    function apiPostSoftDeleteProductCategoryDetail(Request $request)
    {

        $rules = [
            'category_id' =>  'required|exists:categories,category_id',
        ];

        $errorMessages = array(
            'category_id.exists' =>  'Category does not exists'
        );

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails())
        {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        }
        else
        {

            //$category = Category::where('category_id', $request->category_id)
                //->update(['is_active'=>IS_ACTIVE_NO]);

            $category = Category::where('category_id', $request->category_id)
                ->delete();

            if($category)
            {

                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "Category deleted successfully!",
                    "data"      =>  []
                ];

                return response()->json($responseArray);

            }
            else
            {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "Unable to perform this action!",
                    "data"      =>  [
                        "error" =>  ["Requested category not found"]
                    ]
                ];

                return response()->json($responseArray);

            }
        }
    }

    //PARENT CHILD CATEGORY DATA FUNCTION
    function apiGetViewCategoriesInParentChildOrder(Request $request) {

        $categories = Category::select('category_id', 'category_name', 'category_parent_id')
            ->orderBy('category_parent_id', 'asc')
            ->get();

        $output = [];

        foreach($categories as $category)
        {
            if($category->category_parent_id == 0)
            {

                $output[] = array(
                    'category_id'    =>  $category->category_id,
                    'category_name' =>  $category->category_name,
                    'children'  =>  $this->__fetchChildCategory($categories, $category->category_id)
                );

            }
        }
        if(!empty($output))
        {
            $responseArray = [
                "status"    =>  true,
                "message"   =>  "Categories list found",
                "data"      =>  $output
            ];

            return response()->json($responseArray);
        }
        else
        {
            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  [
                    "error" =>  ["Categories not found"]
                ]
            ];

            return response()->json($responseArray);
        }
    }

    //PARENT CHILD CATEGORY DATA SUPPORT FUNCTION
    function __fetchChildCategory($categories, $parentId)
    {
        $output = [];

        foreach($categories as $category)
        {

            if($category->category_parent_id == $parentId)
            {
                $output[] = array(
                    'category_id'    =>  $category->category_id,
                    'category_name'  =>  $category->category_name,
                    'category_parent_id' => $category->category_parent_id,
                );
            }

        }

        return $output;

    }

    //CHILD CATEGORY DATA AGAINST PARENT FUNCTION
    function apiPostAllChildCategoriesAgainstParent(Request $request) {

        $rules = [
            'category_parent_id' =>  'required',
        ];

        $errorMessages = array(
            'category_parent_id.exists' =>  'Category Parent ID does not exists'
        );

        $validator = Validator::make($request->all(), $rules, $errorMessages);

        if($validator->fails()) {

            $responseArray = [
                "status"    =>  false,
                "message"   =>  "Unable to perform this action!",
                "data"      =>  $validator->messages()
            ];

            return response()->json($responseArray);

        } else {

            $categoriesChildData = Category::select('category_id', 'category_name', 'category_parent_id','is_active','image')
                ->where(['category_parent_id' => $request->category_parent_id, 'is_active'=>IS_ACTIVE_YES])
                ->orderBy('category_parent_id', 'asc')
                ->get();

            if($categoriesChildData) {

                $output = [];

                foreach($categoriesChildData as $category) {

                    $output[] = array(
                        'category_id'    =>  $category->category_id,
                        'category_name' =>  $category->category_name,
                        'category_parent_id' => $category->category_parent_id,
                        'is_active' => $category->is_active,
                        'image' => asset(IMAGE_PATH_PARENT_CATEGORY_IMAGES . '/' . $category->image)
                    );
                }


                $responseArray = [
                    "status"    =>  true,
                    "message"   =>  "List of category found",
                    "data"      =>   [
                        "category_list" =>  $output
                    ]
                ];

                return response()->json($responseArray);

            } else {

                $responseArray = [
                    "status"    =>  false,
                    "message"   =>  "category not found",
                    "data"      =>  []
                ];
                return response()->json($responseArray);
            }
        }
    }
}
