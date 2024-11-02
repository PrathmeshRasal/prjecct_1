<?php

use App\Http\Controllers\Api\AdminPostInquiryController;
use App\Http\Controllers\Api\AdminProductReviewController;
use App\Http\Controllers\Api\AdminProductsBulkUploadController;
use App\Http\Controllers\Api\News\Admin\AdminNewsController;
use App\Http\Controllers\Api\News\Public\PublicNewsController;
use App\Http\Controllers\Api\PublicProductReviewController;
use App\Http\Controllers\Api\Slider\Admin\AdminHomePageSliderController;
use App\Http\Controllers\Api\Slider\Public\PublicHomePageSliderController;
use App\Http\Controllers\Hariom\CustomerController;
use App\Http\Controllers\Hariom\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AdminCategoryController;
use App\Http\Controllers\Api\AdminProductController;
use App\Http\Controllers\Api\UserAccountController;
use App\Http\Controllers\Api\AdminProductTagController;
use App\Http\Controllers\Api\AdminUserProductFoundFeedbackController;
use App\Http\Controllers\Api\PublicCompanyController;
use App\Http\Controllers\Api\PublicProductController;
use App\Http\Controllers\Api\PublicUserProductFoundFeedbackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Hariom APIs
Route::prefix('public')->group(function () {
    Route::post('/user-registration', [UserController::class, 'apiUserRegistration'])->name('api-public-post-public-user-registration');

    Route::post('/servicer-registration', [UserController::class, 'apiServicerRegistration'])->name('api-public-post-public-servicer-registration');

});


Route::prefix('account')->group(function () {

    //Admin Login API's
    Route::post('admin-login', [UserAccountController::class, 'apiPostAdminLogin'])->name('api-admin-post-admin-login');

    Route::post('user-login', [UserAccountController::class, 'apiPostUserLogin'])->name('api-admin-post-user-login');

    Route::post('servicer-login', [UserAccountController::class, 'apiPostServicerLogin'])->name('api-admin-post-servicer-login');

});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('user-approval', [UserController::class, 'apiUserApproval']);

    Route::post('/update-machine-details', [CustomerController::class, 'apiUpdateMachinedetails'])->name('api-admin-post-update-machine-details');

    Route::get('/customer-list', [UserAccountController::class, 'apiGetCustomerList'])->name('api-admin-get-customer-list');

    Route::get('/servicer-list', [UserAccountController::class, 'apiGetServicerList'])->name('api-admin-get-servicer-list');

    Route::get('/get-user', [UserAccountController::class, 'apiGetUser'])->name('api-admin-get-user');

    Route::post('/delete-user', [UserAccountController::class, 'apiPostDeleteUser'])->name('api-admin-delete-user');

    Route::get('/service-request-list', [CustomerController::class, 'apiGetServiceRequestList'])->name('api-admin-get-service-request-list');

    Route::get('/service-request-open-list', [CustomerController::class, 'apiGetServiceRequestOpenList'])->name('api-admin-get-service-request-open-list');

    Route::get('/service-request', [CustomerController::class, 'apiGetServiceRequest'])->name('api-admin-get-service-request');

});

Route::prefix('servicer')->middleware(['auth:sanctum', 'servicer'])->group(function () {

    Route::post('/update-machine-details', [CustomerController::class, 'apiUpdateMachinedetails'])->name('api-servicer-post-update-machine-details');

    Route::get('/service-request-open-list', [CustomerController::class, 'apiGetServiceRequestOpenList'])->name('api-servicer-get-service-request-open-list');

    Route::get('/service-request-accepted-list', [CustomerController::class, 'apiGetServiceRequestAcceptedList'])->name('api-servicer-get-service-request-accepted-list');

    Route::get('/service-request', [CustomerController::class, 'apiGetServiceRequest'])->name('api-servicer-get-service-request');

});

Route::prefix('customer')->middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::post('/add-machine-details', [CustomerController::class, 'apiAddMachineDetails'])->name('api-customer-post-add-machine-details');

    // Route::post('/update-machine-details', [CustomerController::class, 'apiUpdateMachinedetails'])->name('api-customer-post-update-machine-details');
});

//end hariom APIs

Route::prefix('admin')->middleware(['auth:sanctum', 'company.employee'])->group(function () {

    // News

    Route::post('api-post-store-news', [AdminNewsController::class, 'postStoreNews'])->name('api-post-store-news');

    Route::post('api-post-update-news', [AdminNewsController::class, 'postUpdateNews'])->name('api-post-update-news');

    Route::post('api-delete-news', [AdminNewsController::class, 'deleteNews'])->name('api-delete-news');

    Route::get('api-get-single-news-list', [AdminNewsController::class, 'getSingleNewsList'])->name('api-get-single-news-list');

    Route::get('api-get-paginated-news-list', [AdminNewsController::class, 'getPaginatedNewsList'])->name('api-get-paginated-news-list');





    // image slider
    Route::post('api-post-homepage-slider-store', [AdminHomePageSliderController::class, 'apiPostHomePageSliderStore'])->name('api-post-homepage-slider-store');

    Route::post('api-post-homepage-slider-update', [AdminHomePageSliderController::class, 'apiPostHomePageSliderUpdate'])->name('api-post-homepage-slider-update');

    Route::post('api-post-homepage-slider-delete', [AdminHomePageSliderController::class, 'apiPostHomePageSliderDelete'])->name('api-post-homepage-slider-delete');

    Route::get('api-get-homepage-slider-specific-id', [AdminHomePageSliderController::class, 'apiGetHomePageSliderSpecificId'])->name('api-get-homepage-slider-specifi-id');

    Route::get('api-get-homepage-slider-pagination', [AdminHomePageSliderController::class, 'apiGetHomePageSliderPagination'])->name('api-get-homepage-pagination');


    //Admin Category API's
    Route::get('/api-get-view-all-categories', [AdminCategoryController::class, 'apiGetViewAllCategories'])->name('api-get-view-all-categories');
    Route::post('/api-post-product-category', [AdminCategoryController::class, 'apiPostProductCategoryDetails'])->name('api-post-product-category');
    Route::post('/api-post-view-single-category', [AdminCategoryController::class, 'apiPostViewSingleCategory'])->name('api-post-view-single-category');
    Route::post('/api-post-update-product-category', [AdminCategoryController::class, 'apiPostUpdateProductCategory'])->name('api-post-update-product-category');
    Route::post('/api-post-delete-product-category', [AdminCategoryController::class, 'apiPostSoftDeleteProductCategoryDetail'])->name('api-post-delete-product-category');
    Route::post('/api-post-view-categories-parent-child-order', [AdminCategoryController::class, 'apiPostAllChildCategoriesAgainstParent'])->name('api-post-view-categories-parent-child-order');
    Route::get('/view-categories-parent-child-order', [AdminCategoryController::class, 'apiGetViewCategoriesInParentChildOrder'])->name('api-admin-get-view-categories-in-parent-child-order');

    // Admin Product API's
    Route::get('/view-all-products', [AdminProductController::class, 'adminApiGetViewAllProducts'])->name('admin-api-get-view-all-products');
    Route::post('/add-new-product', [AdminProductController::class, 'adminApiPostAddNewProduct'])->name('admin-api-post-add-new-product');
    Route::post('/view-single-product', [AdminProductController::class, 'adminApiGetViewSingleProduct'])->name('admin-api-get-view-single-product');
    Route::post('/edit-product', [AdminProductController::class, 'adminApiPostEditProduct'])->name('admin-api-post-edit-product');
    Route::post('/delete-product', [AdminProductController::class, 'adminApiPostSoftDeleteProduct'])->name('admin-api-post-soft-delete-product');

    Route::post('/get-product-specifications', [AdminProductController::class, 'adminApiGetAllProductSpecification'])->name('admin-api-get-product-specifications');
    Route::post('/get-product-highlights', [AdminProductController::class, 'adminApiGetAllProductHighlights'])->name('admin-api-get-product-highlights');

    Route::post('/get-product-image-details', [AdminProductController::class, 'adminApiGetAllProductImageDetails'])->name('admin-api-get-product-image-details');
    Route::post('/post-product-image-details', [AdminProductController::class, 'adminApiPostProductImageDetails'])->name('admin-api-post-product-image-details');
    Route::post('/delete-product-image-details', [AdminProductController::class, 'adminApiPostDeleteProductImageDetails'])->name('admin-api-post-delete-product-image-details');

    // Admin delete product's brochure or video API
    Route::post('/delete-product-brochure-or-intro-video-details', [AdminProductController::class, 'adminApiPostDeleteProductBrochureOrIntroVideoDetails'])->name('admin-api-post-delete-product-brochure-or-intro-video-details');

    // Admin TAG API's
    Route::get('/view-all-tags', [AdminProductTagController::class, 'apiGetViewAllTags'])->name('api-admin-get-view-all-tags');
    Route::post('/add-new-tag', [AdminProductTagController::class, 'apiPostAddNewTag'])->name('api-admin-post-add-new-tag');
    Route::post('/edit-tag', [AdminProductTagController::class, 'apiPostEditTag'])->name('api-admin-post-edit-tag');
    Route::post('/view-single-tag', [AdminProductTagController::class, 'apiGetSingleTagView'])->name('api-admin-get-view-single-tag');
    Route::post('/delete-tag', [AdminProductTagController::class, 'apiPostSoftDeleteTag'])->name('api-admin-post-soft-delete-tag');

    //ADMIN GET CONTACT US DATA API's
    Route::get('/public-get-contact-us-details', [PublicProductController::class, 'apiGetPublicContactUsDetails'])->name('api-get-public-contact-us-details');
    Route::get('/public-get-contact-us-details-sheet', [PublicProductController::class, 'apiGetPublicContactUsDetailsSheet'])->name('api-get-public-contact-us-details-sheet');

    //ADMIN GET POST INQUIRY API
    Route::get('/api-get-view-all-post-inquiry-details', [AdminPostInquiryController::class, 'apiGetViewAllPostInquiryDetails'])->name('api-get-view-all-post-inquiry-details');
    Route::get('/api-get-view-all-post-inquiry-details-sheet', [AdminPostInquiryController::class, 'apiGetViewAllPostInquiryDetailsSheet'])->name('api-get-view-all-post-inquiry-details-sheet');

    //ADMIN GET POST ALL REVIEWS API
    Route::get('/api-get-view-all-product-reviews-list', [AdminProductReviewController::class, 'apiGetViewAllProductReviewsList'])->name('api-get-view-all-product-reviews-list');
    Route::post('/api-post-toggle-approval-product-review', [AdminProductReviewController::class, 'apiPostToggleApprovalProductReview'])->name('api-post-toggle-approval-product-review');
    Route::post('/api-post-delete-product-review', [AdminProductReviewController::class, 'apiPostDeleteProductReview'])->name('api-post-delete-product-review');

    //ADMIN GET ALL USER PRODUCT FOUND FEEDBACKS API
    Route::get('/api-get-view-all-user-product-found-feedbacks-list', [AdminUserProductFoundFeedbackController::class, 'apiGetViewAllUserProductFoundFeedbacksList'])->name('api-get-view-all-user-product-found-feedbacks-list');

    //ADMIN BULK UPLOAD API's
    Route::post('/api-post-products-bulk-upload', [AdminProductsBulkUploadController::class, 'apiPostProductsBulkUpload'])->name('api-post-products-bulk-upload');

});


Route::prefix('public')->group(function () {
    //Hariom APIs
    // Route::post('/user-registration', [UserController::class, 'apiUserRegistration'])->name('api-public-post-public-user-registration');

     // image slider

    Route::get('api-get-homepage-slider-list', [PublicHomePageSliderController::class, 'apiGetHomePageSliderPublicList'])->name('api-get-homepage-slider-list');

    // NEWS PUBLIC API

    Route::get('api-get-public-paginated-news-list', [PublicNewsController::class, 'getPublicPaginatedNewsList'])->name('api-get-public-paginated-news-list');
    Route::get('api-get-public-single-news-list', [PublicNewsController::class, 'getPublicSingleNewsList'])->name('api-get-public-single-news-list');


    // PUBLIC WEBSITE CATEGORY API's
    Route::get('/public-view-categories-parent-child', [PublicProductController::class, 'apiGetPublicViewCategoriesInParentChild'])->name('api-public-get-public-view-categories-parent-child');
    Route::post('/public-view-product-list-details', [PublicProductController::class, 'apiPostPublicViewProductListDetails'])->name('api-public-post-public-view-product-list-details');
    Route::post('/public-view-product', [PublicProductController::class, 'apiPublicPostViewThisProduct'])->name('api-public-post-view-this-product');
    Route::post('/public-view-category-wise-model-list', [PublicProductController::class, 'apiPublicPostViewCategoryWiseModelList'])->name('api-public-post-public-view-category-wise-model-list');

    // PUBLIC INDUSTRY WISE LIST API
    Route::post('/public-view-industry-wise-products-list', [PublicProductController::class, 'apiPublicPostViewIndustryWiseProductsList'])->name('api-public-post-public-view-industry-wise-products-list');

    // PUBLIC WEBSITE SEARCH API
    Route::post('/public-view-search-products-list', [PublicProductController::class, 'apiGetPublicViewSearchProductsList'])->name('api-public-get-public-view-search-products-list');

    // PUBLIC POPULAR PRODUCTS API
    Route::get('/public-view-popular-products-list', [PublicProductController::class, 'apiGetPublicViewPopularProductsList'])->name('api-public-get-public-view-popular-products-list');

    // PUBLIC POST CONTACT US API's
    Route::post('/public-post-contact-us-details', [PublicProductController::class, 'apiPostPublicContactUsDetails'])->name('api-post-public-contact-us-details');

    // PUBLIC POST INQUIRY API's
    Route::post('/public-post-inquiry-details', [PublicProductController::class, 'apiPostPublicInquiryDetails'])->name('api-post-public-inquiry-details');

    // PUBLIC REVIEW API'S
    Route::post('/public-post-review-details', [PublicProductReviewController::class, 'apiPostPublicReviewDetails'])->name('api-post-public-review-details');

    // PUBLIC COMPANIES LIST API'S
    Route::get('/api-get-view-all-companies-list', [PublicCompanyController::class, 'apiGetViewAllCompaniesList'])->name('api-get-view-all-companies-list');

    // PUBLIC USER PRODUCT FOUND FEEDBACK API'S
    Route::post('/public-post-user-product-found-feedback-details', [PublicUserProductFoundFeedbackController::class, 'apiPostPublicUserProductFoundFeedbackDetails'])->name('api-post-public-user-product-found-feedback-details');
});
