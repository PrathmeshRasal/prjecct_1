<?php

define('ADMIN_MAIL', "sales@hariomengineering.com");

    define('IS_ACTIVE_YES', 1);
    define('IS_ACTIVE_NO', 0);

    define('USER_TYPE_CUSTOMER', 1);
    define('USER_TYPE_ADMIN', 2);
    define('USER_TYPE_STORE_EMPLOYEE', 3);

    define('MIN_PRODUCT_INT_VALUE', 10000000);
    define('MAX_PRODUCT_INT_VALUE', 16777215);


    // environment public path

    define('ENV_PUBLIC_PATH', env('ENV_PUBLIC_PATH', 'public/'));


    // file upload path

    define('IMAGE_PATH_PARENT_CATEGORY_IMAGES', 'parent-category-images');
    define('IMAGE_PATH_PRODUCT_IMAGES', 'products-images');
    define('IMAGE_PATH_BLOG_IMAGES', 'blog-images');

    define('PRODUCT_BROCHURE_PDFS', 'product-brochures');

    define('PRODUCT_VIDEOS', 'product-videos');

    define('PRODUCT_OFFERS_FILES', 'product-offers');

    define('PRODUCT_MARKETING_IMAGES', 'product-marketing-images');

    define('PATH_IMEI_NUMBERS_CSV', 'imei-numbers');

    define('ERROR_MSG_UNABLE_TO_PERFORM_THIS_ACTION', "Unable to perform this action!");

    // billing address type

    define('ADDRESS_TYPE_BILLING', 1);
    define('ADDRESS_TYPE_SHIPPING', 2);

    define('ADDRESS_LOCATION_TEXT', [1=>'Home', 2=>'Office']);
    define('ADDRESS_TYPE_TEXT', [1=>'Billing', 2=>'Shipping']);

    define('DEFAULT_CATEGORY_IMAGE', 'default.jpeg');
    define('DEFAULT_PRODUCT_IMAGE', 'default.png');
    define('DEFAULT_BLOG_IMAGE', 'default.jpeg');

    define('IN_MM_CONSTANTS_ARRAY', ['in mm', 'in  mm']);
    define('IN_INCH_CONSTANTS_ARRAY', ['in inch', 'in  inch']);

    //generate unique number

    function generateUniqueNumber($min, $max)
    {
        return random_int($min, $max);
    }
