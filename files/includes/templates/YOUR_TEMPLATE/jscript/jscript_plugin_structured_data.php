<?php

declare(strict_types=1);
/* This file MUST be loaded by html <head> since it generates meta tags.
 * DO NOT LET YOUR IDE RE-FORMAT THE CODE STRUCTURE: it is structured so the html SOURCE is readable/the parentheses line up.
 * @author: torvista
 * @link: https://github.com/torvista/Zen_Cart-Structured_Data
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version: torvista 04/09/2023
 */
/** directives for phpStorm code inspector
 * @var breadcrumb $breadcrumb
 * @var $canonicalLink
 * @var $current_page
 * @var $current_page_base
 * @var queryFactory $db
 * @var $product_id
 * @var sniffer $sniffer
 */
if (!defined('PLUGIN_SDATA_ENABLE') || PLUGIN_SDATA_ENABLE !== 'true') {
    return;
}

//***** SITE-SPECIFIC constants additional to the Admin constants ************

// Fallback/default Google category ID (up to 6 digits). eg. '5613'	= Vehicles & Parts, Vehicle Parts & Accessories
// This is used if a product does not have a specific category defined https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.xls
define('PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY', '');

//REVIEWS
// If there are no reviews for a product, use a default value to stop Google warnings
define('PLUGIN_SDATA_REVIEW_USE_DEFAULT', 'true');
// If there are no reviews for a product, average rating
define('PLUGIN_SDATA_REVIEW_DEFAULT_VALUE', '3');
// If the review date is null (should not occur/it's an error in the entry in the reviews table), use this date
define('PLUGIN_SDATA_REVIEW_DEFAULT_DATE', '2020-06-04 13:48:39');

// Fallback/default weight if product weight in database is not set
define('PLUGIN_SDATA_DEFAULT_WEIGHT', '0.3');

// ItemAvailability
// https://developers.google.com/search/docs/appearance/structured-data/product
// It seems there is no option for an Out Of Stock product that is only ordered from the supplier on demand...best option seems to be backorder... but this needs a date. Use today's date + some days delay.
define('PLUGIN_SDATA_OOS_DEFAULT', 'BackOrder'); // as per key in $itemAvailability below
//Days to add to today's date for BackOrder/PreOrder
define('PLUGIN_SDATA_OOS_AVAILABILITY_DELAY', '10');

// Merchant Return Policy
// https://schema.org/MerchantReturnPolicy
// https://developers.google.com/search/docs/appearance/structured-data/product#returns
// applicableCountry
define('PLUGIN_SDATA_RETURNS_APPLICABLE_COUNTRY', ''); // country to which the returns policy applies: 2-char ISO. I failed to figure out a structure where multiple countries can be used.
define('PLUGIN_SDATA_RETURNS_POLICY_COUNTRY', ''); // country to which the product is to be returned/STORE Country: 2-char ISO.
// returnPolicyCategory
define('PLUGIN_SDATA_RETURNS_POLICY', 'finite'); // 'finite' / 'not_permitted' / 'unlimited':  the returns category as per key of $returnPolicyCategory defined below.
// merchantReturnDays.  Only required if PLUGIN_SDATA_RETURNS_POLICY = finite
define('PLUGIN_SDATA_RETURNS_DAYS', '14'); // limit of period (days) within which a product can be returned.
// returnMethod
define('PLUGIN_SDATA_RETURNS_METHOD', 'mail'); // 'kiosk' / 'mail' / 'store': method of returning a product, as per key in $returnMethod defined below.
// returnShippingFeesAmount
define('PLUGIN_SDATA_RETURNS_FEES', '0'); // cost to return a product. Use 0 or a decimal.

//***** eof SITE-SPECIFIC constants additional to the Admin constants ************

define('PLUGIN_SDATA_MAX_DESCRIPTION', 5000); // maximum characters allowed in the description (Google)
define('PLUGIN_SDATA_MAX_NAME', 150); // maximum characters allowed in the name (Google)

//when a site uses a url rewriter, the native url (www.shop.com/index.php?main_page=product_info&cPath=1_4&products_id=1) is replaced by a more "friendly" one (www.shop.com/products/widget1).
//If this option is set to true, the native url (www.shop.com/index.php?main_page=product_info&cPath=1_4&products_id=1) is always used.
//define ('PLUGIN_SDATA_NATIVE_URL', true); // not in use

if (defined('PLUGIN_SDATA_PRICE_CURRRENCY')) {//sic: correct old typo
    $db->Execute("UPDATE `configuration` SET `configuration_key`= 'PLUGIN_SDATA_PRICE_CURRENCY' WHERE `configuration_key`= 'PLUGIN_SDATA_PRICE_CURRRENCY'");
}

$debug_sd = false; // set to true (boolean) to display debugging info. Changes from the gods are imposed irregularly, so I've left a lot of ugly debug output available. Some outputs to the viewport, the rest in the head: view with Browser Developer Tools. Most has the line number prefixed so you can search in Developer tools for the line number.
// to show formatted contents/type of a variable/array, use this inbuilt function: sd_printvar($variableName);

// defaults
$image_default = false; //if true, use a substitute generic image if no specific image found/exists
$facebook_type = 'business.business';

// Schema arrays
// ItemAvailability options
$itemAvailability = [
    'BackOrder' => 'https://schema.org/BackOrder',                     // The item is on back order. BackOrder needs a date for when it will become available
    'Discontinued' => ' https://schema.org/Discontinued',              // The item has been discontinued.
    'InStock' => 'https://schema.org/InStock',                         // The item is in stock.
    'InStoreOnly' => 'https://schema.org/InStoreOnly',                 // The item is only available for purchase in store.
    'LimitedAvailability' => 'https://schema.org/LimitedAvailability', // The item has limited availability.
    'OnlineOnly' => 'https://schema.org/OnlineOnly',                   // The item is available online only.
    'OutOfStock' => 'https://schema.org/OutOfStock',                   // The item is currently out of stock.
    'PreOrder' => 'https://schema.org/PreOrder',                       // The item is available for pre-order: buying in advance of a NEW product being released for sale. PreOrder needs a date for when product will be released
    'PreSale' => 'https://schema.org/PreSale',                         // The item is available for ordering and delivery NOW before it is released for general availability.
    'SoldOut' => 'https://schema.org/SoldOut'                          // The item has been sold out.
];

// Product Condition options
$itemCondition = ['new' => 'NewCondition', 'used' => 'UsedCondition', 'refurbished' => 'RefurbishedCondition'];

// Merchant Return Policy options
$returnPolicyCategory = [
    'finite' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
    'not_permitted' => 'https://schema.org/MerchantReturnNotPermitted',
    'unlimited' => 'https://schema.org/MerchantReturnUnlimitedWindow',
    //'none' => 'https://schema.org/MerchantReturnUnspecified' // 'this Schema option is not supported by Google
];
// used only with 'finite' and 'unlimited'
$returnMethod = [
    'kiosk' => 'https://schema.org/ReturnAtKiosk',
    'mail' => 'https://schema.org/ReturnByMail',
    'store ' => 'https://schema.org/ReturnInStore'
];
// eof Schema arrays

/** parse string to make it suitable for embedding in the head
 * @param $string
 * @return string
 */
function sdata_prepare_string($string): string
{
    $string = html_entity_decode(trim($string), ENT_COMPAT, CHARSET);//convert html entities to characters
    $string = str_replace('</p>', '</p> ', $string); //add a space to separate text when tags are removed
    $string = str_replace('<br>', '<br> ', $string); //add a space to separate text when tags are removed
    $string = strip_tags($string);//remove html tags
    $string = str_replace(["\r\n", "\n", "\r"], '', $string);//remove LF, CR
    $string = preg_replace('/\s+/', ' ', $string);//remove multiple spaces
    return $string;
}

/** truncate long descriptions as legibly as possible
 * @param $string
 * @param $max_length
 * @return string
 */
function sdata_truncate($string, $max_length): string
{
    $string_json = json_encode($string);
    $string_json_length = strlen($string_json);
    //encoded multibyte characters increase the length
    if ($string_json_length > $max_length + 2) {//allow for enclosing double quotes
        //remove the enclosing double quotes
        $string_json_truncated = trim($string_json, '"');
        //truncate to $max_length, allowing for space to add ellipsis
        $string_json_truncated = substr($string_json_truncated, 0, $max_length - 3);
        //find last backslash from json encoding
        $position_last_backslash = strrpos($string_json_truncated, '\\');
        //check for bisected encoding e.g.\u00f3 cropped to less than 6 chars
        if ($position_last_backslash !== false && (strlen($string_json_truncated) - ($position_last_backslash + 1) < 6)) {
            $string_json_truncated = substr($string_json_truncated, 0, $position_last_backslash);
        }
        //add enclosing double quotes
        $string = json_decode('"' . $string_json_truncated . '..."');
    }
    return $string;
}

/** display variable/array with name and type, debugging only
 * @param $a
 * @return void
 */
function sdata_printvar($a): void
{
    $backtrace = debug_backtrace()[0];
    $fh = fopen($backtrace['file'], 'rb');
    $line = 0;
    $code = '';
    while (++$line <= $backtrace['line']) {
        $code = fgets($fh);
    }
    fclose($fh);
    $name = '';
    if ($code !== false) {
        preg_match('/' . __FUNCTION__ . '\s*\((.*)\)\s*;/u', $code, $name);
    }
    echo '<pre>';
    if (!empty($name[1])) {
        echo '<strong>' . trim($name[1]) . '</strong> (' . gettype($a) . "):\n";
    }
    //var_export($a);
    print_r($a);
    echo '</pre><br>';
}

//defaults defined to prevent php notices
$breadcrumb_schema = [];
$category_name = '';
$description = '';
$image = '';
$image_alt = '';
$manufacturer_name = '';
$product_base_displayed_price = '';
$product_base_mpn = '';
$product_base_sku = '';
$product_base_stock = 0;
$product_id = 0;
$reviewsArray = empty($reviewsArray) ? [] : $reviewsArray;//$reviewsArray already exists on the product review page
$title = '';
//breadcrumb
$breadcrumb_trail = $breadcrumb->trail(',');
$breadcrumb_array = explode(',', $breadcrumb->trail(',')); // create array
$breadcrumb_array = preg_replace("/\r|\n/", '', $breadcrumb_array); // remove line feeds
$breadcrumb_array = array_map('trim', $breadcrumb_array); // remove whitespace
$breadcrumb_count = count($breadcrumb_array);
if ($breadcrumb_count > 0) {
    foreach ($breadcrumb_array as $key => $value) {
        $text = strip_tags($value);
        preg_match('/^<a.*?href=(["\'])(.*?)\1.*$/', $value, $m);
        $url = $m[2] ?? '';
        $breadcrumb_schema[$key]['position'] = (int)$key + 1;
        $breadcrumb_schema[$key]['id'] = $url;
        $breadcrumb_schema[$key]['name'] = $text;
    }
    if ($breadcrumb_schema[$breadcrumb_count - 1]['id'] === '' && isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
        $breadcrumb_schema[$breadcrumb_count - 1]['id'] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
}
$url = $canonicalLink; //may be native or friendly if a url rewriter in use: see note above for PLUGIN_SDATA_NATIVE_URL
/*cludged solution to use the native urls. Don't like it so not implemented/incomplete. It should catch the parameters passed to notify_sefu_intercept which the url rewriter should be using
if(PLUGIN_SDATA_NATIVE_URL === true) { //always use the native url
    $url = trim(HTTP_SERVER . DIR_WS_CATALOG . ($current_page === 'index' ? '' : 'index.php?main_page=' . $current_page . '&' . $_SERVER['QUERY_STRING']), '&');
}*/

//image
if (PLUGIN_SDATA_FOG_DEFAULT_IMAGE !== '') {
    $image_default_facebook = PLUGIN_SDATA_FOG_DEFAULT_IMAGE;
} elseif (PLUGIN_SDATA_LOGO !== '') {
    $image_default_facebook = PLUGIN_SDATA_LOGO;
} else {
    $image_default_facebook = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE;
}
if (PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE !== '') {
    $image_default_twitter = PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE;
} elseif (PLUGIN_SDATA_LOGO !== '') {
    $image_default_twitter = PLUGIN_SDATA_LOGO;
} else {
    $image_default_twitter = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE;
}

if ($debug_sd) {
    echo 'PLUGIN_SDATA_FOG_DEFAULT_IMAGE=' . PLUGIN_SDATA_FOG_DEFAULT_IMAGE . '<br>';
    echo 'PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE=' . PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE . '<br>';
    echo 'PLUGIN_SDATA_LOGO=' . PLUGIN_SDATA_LOGO . '<br>';
    echo '$image_default_facebook=' . $image_default_facebook . '<br>';
    echo '$image_default_twitter=' . $image_default_twitter . '<br>';
}

$is_product_page = (substr($current_page_base, -5) === '_info' && (!empty($_GET['products_id']) && zen_products_lookup($_GET['products_id'], 'products_status') === '1')
    && zen_get_info_page($_GET['products_id']) === $current_page_base);
if ($is_product_page) {//product page only
    if ($debug_sd) {
        echo __LINE__ . ' is product page<br>';
    }
    //get product info

    $sql = 'SELECT p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_date_added, p.products_weight, p.products_tax_class_id, p.products_priced_by_attribute, p.product_is_call, pd.products_name, pd.products_description
           FROM ' . TABLE_PRODUCTS . ' p, ' . TABLE_PRODUCTS_DESCRIPTION . ' pd
           WHERE p.products_id = ' . (int)$_GET['products_id'] . '
           AND pd.products_id = p.products_id
           AND pd.language_id = ' . (int)$_SESSION['languages_id'];
    $product_info = $db->Execute($sql);

    $product_id = (int)$product_info->fields['products_id'];
    $product_name = sdata_prepare_string($product_info->fields['products_name']);
    $description = sdata_prepare_string($product_info->fields['products_description']);
    $title = htmlspecialchars(STORE_NAME . ' - ' . $product_info->fields['products_name'], ENT_QUOTES);
    $weight = (float)($product_info->fields['products_weight'] === '0' ? PLUGIN_SDATA_DEFAULT_WEIGHT : $product_info->fields['products_weight']);
    $tax_class_id = $product_info->fields['products_tax_class_id'];
    if ($product_info->fields['product_is_call'] === '1') {
        $product_base_displayed_price = 0;
    } else {
        $product_base_displayed_price = round(
            zen_get_products_actual_price($product_id) * (1 + zen_get_tax_rate($tax_class_id) / 100),
            2
        );//shown price with tax, decimal point (not comma), two decimal places.
    }
    $product_date_added = $product_info->fields['products_date_added'];//should never be default '0001-01-01 00:00:00'
    $manufacturer_name = zen_get_products_manufacturers_name((int)$_GET['products_id']);
    $product_base_stock = $product_info->fields['products_quantity'];

//BackOrder/PreSales have dates added
    $oosItemAvailability = array_key_exists(PLUGIN_SDATA_OOS_DEFAULT, $itemAvailability) ? $itemAvailability[PLUGIN_SDATA_OOS_DEFAULT] : $itemAvailability['OutOfStock'];
    if (PLUGIN_SDATA_OOS_DEFAULT === 'BackOrder' || PLUGIN_SDATA_OOS_DEFAULT === 'PreSales') {
        $backPreOrderDate = date('Y-m-d', strtotime('+' . (int)PLUGIN_SDATA_OOS_AVAILABILITY_DELAY . ' days'));
    } else {
        $backPreOrderDate = '';
    }

    //sku: the Merchant-specific product identifier (not necessarily the same as the manufacturer mpn / gtin)
    $product_base_sku = $product_info->fields['products_model'];

    /*
    The following fields are not part of Zen Cart product data and so will require manually adding to your database as per product type.
     These initial values mpn/productID to $product_base_sku, to be overwritten later/or not...
     $product_base_mpn //manufacturers part number
     $product_base_gtin //a standardised international code UPC / GTIN-12 / EAN / JAN / ISBN / ITF-14
     $product_base_productID //an optional non-standardised code: a possible use may be the shop base/false sku when attributes stock has the real/correct sku?
     $product_base_google_product_category //google product category https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.xls
     */
    $product_base_mpn = '';
    $product_base_gtin = '';
    $product_base_productID = $product_info->fields['products_model'];
    $product_base_gpc = PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY;

//bof ******************CUSTOM CODE for extra product fields for mpn, ean and google product category***********************/
//here you will need to edit things as per the names and contents of your extra database columns
    /* examples of sql for adding extra fields for product codes: edit as necessary for your site
    ALTER TABLE `products` ADD `products_mpn` VARCHAR(32) NOT NULL DEFAULT;
    ALTER TABLE `products` ADD `products_ean` VARCHAR(13) NOT NULL DEFAULT '' AFTER `products_mpn`;
    ALTER TABLE `products` ADD `products_google_product_category` VARCHAR(6) NOT NULL DEFAULT '' AFTER `products_ean`;
    category in SCHEMA must be text, not a google_product_category number https://support.google.com/google-ads/thread/57687299?hl=en
    */
    $extra_fields = [];
    if ($sniffer->field_exists(TABLE_PRODUCTS, 'products_mpn')) {
        $extra_fields[] = 'products_mpn';
    }
    if ($sniffer->field_exists(TABLE_PRODUCTS, 'products_ean')) {
        $extra_fields[] = 'products_ean';
    }
    if ($sniffer->field_exists(TABLE_PRODUCTS, 'products_google_product_category')) {
        $extra_fields[] = 'products_google_product_category';
    }
    if (count($extra_fields) > 0) {
        $extra_fields = implode(', ', $extra_fields);
        $sql = 'SELECT ' . $extra_fields . ' FROM ' . TABLE_PRODUCTS . ' WHERE products_id = ' . $product_id;
        $product_codes = $db->Execute($sql);
        $product_base_mpn = !empty($product_codes->fields['products_mpn']) ? $product_codes->fields['products_mpn'] : '';//manufacturer part number
        $product_base_gtin = !empty($product_codes->fields['products_ean']) ? $product_codes->fields['products_ean'] : '';//manufacturer assigned global code
        $product_base_gpc = (int)(!empty($product_codes->fields['products_google_product_category']) ? $product_codes->fields['products_google_product_category']
            : PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY);//google merchant taxonomy
    }
//eof ******************CUSTOM CODE for extra product fields for mpn, ean and google product category***********************/

//sku/mpn/gtin, price, stock may all vary per attribute
//Attributes handling info: https://www.schemaapp.com/newsletter/schema-org-variable-products-productmodels-offers/#
    $product_attributes = false;
    $attribute_stock_handler = 'not_defined';
    $attribute_lowPrice = 0;
    $attribute_highPrice = 0;
    $offerCount = 1; //but what the hell is it? Sum of all variants in stock or just the number of variants? Should not be zero.

    if ($product_info->fields['product_is_call'] === '0' && zen_has_product_attributes($product_id)) {
        $product_attributes = [];
        $attribute_prices = [];

// Get attribute info
        $sql = 'SELECT patrib.products_attributes_id, patrib.options_id, patrib.options_values_id, patrib.options_values_price, patrib.products_attributes_weight, patrib.products_attributes_weight_prefix, popt.products_options_name, poptv.products_options_values_name
                    FROM ' . TABLE_PRODUCTS_OPTIONS . ' popt
                    LEFT JOIN ' . TABLE_PRODUCTS_ATTRIBUTES . ' patrib ON (popt.products_options_id = patrib.options_id)
                    LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES . ' poptv ON (poptv.products_options_values_id = patrib.options_values_id AND poptv.language_id = popt.language_id)
                    WHERE patrib.products_id = ' . $product_id . '
                    AND popt.language_id = ' . (int)$_SESSION['languages_id'] . '
                    ORDER BY popt.products_options_name, poptv.products_options_values_name';
        $results = $db->Execute($sql);

        foreach ($results as $attribute) {
            if (zen_get_attributes_valid($product_id, $attribute['options_id'], $attribute['options_values_id'])) {//skip "display only"
                $product_attributes[$attribute['products_attributes_id']]['option_name_id'] = $attribute['options_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_name'] = $attribute['products_options_name'];
                $product_attributes[$attribute['products_attributes_id']]['option_value_id'] = $attribute['options_values_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_value'] = $attribute['products_options_values_name'];
                $product_attributes[$attribute['products_attributes_id']]['price'] = zen_get_products_price_is_priced_by_attributes($product_id) ? $attribute['options_values_price']
                    : $product_base_displayed_price;
                //unlikely that a product price is 0, so only store non-zero prices to subsequently get the high and low prices
                if ($product_attributes[$attribute['products_attributes_id']]['price'] > 0) {
                    $attribute_prices[] = $product_attributes[$attribute['products_attributes_id']]['price'];
                }
                $product_attributes[$attribute['products_attributes_id']]['weight'] = 0;
                if ($attribute['products_attributes_weight'] !== '0') {
                    $product_attributes[$attribute['products_attributes_id']]['weight'] = (float)(($attribute['products_attributes_weight_prefix'] === '-' ? '-' : '')
                        . $attribute['products_attributes_weight']);
                }
            }
        }
        if (count($attribute_prices) > 0) {
            $attribute_lowPrice = min($attribute_prices);
            $attribute_highPrice = max($attribute_prices);
        } else {
            $attribute_lowPrice = 0;
            $attribute_highPrice = 0;
        }

        if ($debug_sd) {
            echo __LINE__ . ' $attribute_lowPrice=' . $attribute_lowPrice . ' | $attribute_highPrice=' . $attribute_highPrice . '<br>count($product_attributes)=' . count($product_attributes);
            sdata_printvar($product_attributes);
            sdata_printvar($attribute_prices);
        }
//$product_attributes array structure (key is products_attributes_id, ordered by the option value text) example
        /*
            [2682] => Array
                (
                    [option_name_id] => 24
                    [option_name] => SH cable
                    [option_value_id] => 148
                    [option_value] => SH-A01
                    [price] => 26
                )
        */

        /*THIRD PARTY ATTRIBUTE-STOCK CONTROL PLUGINS************************
        The existing array "$product_attributes" needs the extra elements to be added with this structure (although it may have more fields).
        Each shop must add code from where to retrieve the values to load into mpn/gtin. In case I have used ean.
                                    [2682] => Array
                                        (
                                            [price] => 26
                                            [stock] => 99
                                            [sku] => HT-1212
                                            [mpn] => SH-A01
                                            [gtin] => 5055780349776
                                        )
                                */
        switch (true) {
            case (defined('POSM_ENABLE') && POSM_ENABLE === 'true' && is_pos_product($product_id)):
                //using "Products Options Stock Manager": https://vinosdefrutastropicales.com/index.php?main_page=product_info&cPath=2_7&products_id=46
                if ($debug_sd) {
                    echo __LINE__ . ' Attributes: using POSM<br>';
                    sdata_printvar($product_attributes);
                }
                    $attribute_stock_handler = 'posm';

                    if (product_has_pos_attributes($product_id)) {

                    $total_attributes_stock = 0;
                    $posm_records = $db->Execute('SELECT * FROM ' . TABLE_PRODUCTS_OPTIONS_STOCK . ' WHERE products_id = ' . $product_id);
/*
 $posm_record (array):
Array
(
    [pos_id] => 3953
    [products_id] => 6583
    [pos_name_id] => 5
    [products_quantity] => 1
    [pos_hash] => e330ff5e3adedc34f1f9622ccfeb95a8
    [pos_model] => HNW-EVO424BL
    [pos_mpn] => EVO424BL
    [pos_ean] => 5056137206872
    [pos_date] => 2003-01-01
    [last_modified] => 2023-04-03 23:38:08
)
 */
                        $product_attributes = [];
                        $attribute_posm_price_temp = $attribute_lowPrice + ($attribute_highPrice-$attribute_lowPrice)/2;
                    foreach ($posm_records as $key => $posm_record) {
                        $product_attributes[$key]['price'] = $attribute_posm_price_temp;
                        $product_attributes[$key]['stock'] = (int)$posm_record['products_quantity'];
                        $product_attributes[$key]['sku'] = $posm_record['pos_model'];
                        $total_attributes_stock += $posm_record['products_quantity'];

                        //CUSTOM CODING REQUIRED: custom fields will vary per shop ***************************************
                        if ($sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_mpn')) {
                            $product_attributes[$key]['mpn'] = $posm_record['pos_mpn'];
                        }
                        if ($sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_ean')) {
                            $product_attributes[$key]['gtin'] = empty((int)$posm_record['pos_ean']) ? $product_base_gtin : (int)$posm_record['pos_ean'];
                        }
                        //eof CUSTOM CODING REQUIRED***********************************

                    }
                        if ($debug_sd) {
                            echo __LINE__ ;
                            sdata_printvar($product_attributes);
                        }
                }
                    break;

                foreach($product_attributes as $products_attributes_id=>$product_attribute)  {

                    /*
                     * $product_attribute (array):
                        Array
                        (
                            [option_name_id] => 10
                            [option_name] => colour
                            [option_value_id] => 140
                            [option_value] => clear
                            [price] => 0.0000
                            [weight] => 0
                        )
                    //to get POSM record, need to pass array to observer function getOptionsStockRecordArray
                    eg:
                    [option_name_id] =>option_value_id
                (
                    [25] => 392
                    [10] => 48
                )
                    */
                    $posm_options = array($product_attribute['option_name_id'] => $product_attribute['option_value_id']);
                    //mv_printVar($posm_options);die;
                    $posm_record = $posObserver->getOptionsStockRecord($product_id, $posm_options);
                    mv_printVar($posm_record);
                }

            case (defined('STOCK BY ATTRIBUTES')):
                //over to YOU

            case (defined('NUMINIX PRODUCT VARIANTS INVENTORY MANAGER')):
                //over to YOU

            default://Zen Cart default/no handling of attribute stock...so no sku/mpn/gtin possible per attribute
                $attribute_stock_handler = 'zc_default';
                foreach ($product_attributes as $key => $product_attribute) {
                    $product_attributes[$key]['stock'] = $product_base_stock;
                    $product_attributes[$key]['sku'] = $product_base_sku;//as per individual shop
                    $product_attributes[$key]['mpn'] = '';//as per individual shop
                    $product_attributes[$key]['gtin'] = '';//as per individual shop
                }
                $offerCount = (max($product_base_stock, 1));
        }
    }
    if ($debug_sd) {
        echo __LINE__;
        sdata_printvar($product_attributes);
    }
    $product_image = $product_info->fields['products_image'];
    if ($product_image !== '') {
        if (!defined('IH_RESIZE') || IH_RESIZE !== 'yes') {//Image Handler not installed/not in use so get a larger image
            $products_image_extension = substr($product_image, strrpos($product_image, '.'));
            $products_image_base = str_replace($products_image_extension, '', $product_image);
            if (file_exists(DIR_WS_IMAGES . 'large/' . $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extension)) {
                $product_image = 'large/' . $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extension;
            } elseif (file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extension)) {
                $product_image = 'medium/' . $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extension;
            }
        }//Image Handler is in use
        $image = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $product_image;
    } else {//no image defined in product info
        //note PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE is a FULL path with protocol
        $image = (PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE !== '' ? PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE
            : HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE);//if no default image, use standard no-image file.
    }

    $category_id = zen_get_products_category_id($product_id);
    $category_name = zen_get_category_name($category_id, (int)$_SESSION['languages_id']); // ZC158 does not need language parameter

    $image_alt = $product_name;
    $facebook_type = 'product';
} elseif (isset($_GET['cPath'])) { // NOT a product page

    if ($debug_sd) {
        echo __LINE__ . ': $current_page=' . $current_page . ', is NOT product page<br>';
    }

    $cPath_array = explode('_', $_GET['cPath']);
    $category_id = end($cPath_array);
    reset($cPath_array);
    $category_name = zen_get_category_name($category_id, (int)$_SESSION['languages_id']); // ZC158 does not need language parameter
    if (!empty($category_name)) { //a valid category
        $category_image = zen_get_categories_image($category_id);

        if ($debug_sd) {
            echo __LINE__ . ' $category_image=' . $category_image . '<br>';
            echo __LINE__ . ' gettype $category_image=' . gettype($category_image) . '<br>';
        }

        if ($category_image === '' || $category_image === null) {
            $image_default = true;
        } else {
            $image = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . zen_get_categories_image($category_id);
        }
        $description = zen_get_category_description($category_id, (int)$_SESSION['languages_id']) !== '' ? zen_get_category_description($category_id, (int)$_SESSION['languages_id'])
            : META_TAG_DESCRIPTION;
        $product_category_name = $category_name;//used for twitter title, it changes depending on if page is product or category
        $image_alt = $category_name;
        $facebook_type = 'product.group';
        $title = META_TAG_TITLE;
    } else {
        // something wrong: a category with no name/does not exist!
        $image_default = true;
        $image_alt = '';
        $product_category_name = '';
        $title = META_TAG_TITLE;
    }
} else {//some other page - not product or category
    if ($debug_sd) {
        echo __LINE__ . ' is "Other" page<br>';
    }

    $image_default = true;
    //$image_alt = $breadcrumb_this_page;//todo, needed??
    $title = META_TAG_TITLE;
    $description = META_TAG_DESCRIPTION;
}

//$description could be null from META_TAG_DESCRIPTION
if (empty($description)) {
    $description = '';
}
$description = sdata_prepare_string($description);
//build sameAs list
$sameAs_array = explode(', ', PLUGIN_SDATA_SAMEAS);
array_push($sameAs_array, PLUGIN_SDATA_FOG_PAGE, PLUGIN_SDATA_TWITTER_PAGE, PLUGIN_SDATA_GOOGLE_PUBLISHER);
$contact_us = $_GET['main_page'] !== 'contact_us' ? zen_href_link(FILENAME_CONTACT_US, '', 'SSL') : '';
if ($contact_us !== '') {
    $sameAs_array[] = $contact_us;
}//show contact_us on all pages except contact_us
foreach ($sameAs_array as $key => $value) {//remove any empty keys where the constant was not set
    if (empty($value)) {
        unset($sameAs_array[$key]);
    }
}
if (!empty($sameAs_array)) {
    foreach ($sameAs_array as &$profile_page) {
        $profile_page = '"' . $profile_page . '"';
    }
    unset($profile_page);
}
$sameAs = implode(",\n", $sameAs_array);

//build acceptedPaymentMethod list
$PaymentMethod_array = explode(', ', PLUGIN_SDATA_ACCEPTED_PAYMENT_METHODS);
foreach ($PaymentMethod_array as &$payment_method) {
    $payment_method = '"https://purl.org/goodrelations/v1#' . trim($payment_method) . '"';
}
unset($payment_method);
$PaymentMethods = implode(",\n", $PaymentMethod_array);

//build Facebook locales
$locales_array = explode(',', PLUGIN_SDATA_FOG_LOCALES);
$locales_array = array_map('trim', $locales_array);
/* Array example
(
    [0] => 1
    [1] => en_GB
    [2] => 2
    [3] => es_ES
)
*/
$locale_count = count($locales_array);
if ($locale_count > 1 && ($locale_count % 2 === 0)) { // is more than one value and is actually a pair
    $locales_keys_array = [];
    $locales_values_array = [];
    $i = 0;
    while ($i < $locale_count) {
        $locales_keys_array [] = $locales_array[$i]; // returns: 1,2 etc.
        $i += 2;
    }
    $i = 1;
    while ($i < $locale_count) {
        $locales_values_array [] = $locales_array[$i]; // returns: en_GB, es_ES etc
        $i += 2;
    }
    $locales_array = array_combine($locales_keys_array, $locales_values_array);
    $locale = '';
    if (array_key_exists($_SESSION['languages_id'], $locales_array)) {
        $locale = $locales_array[(int)$_SESSION['languages_id']]; // returns: en_GB, es_ES etc
        unset($locales_array[(int)$_SESSION['languages_id']]); // other elements are used as the alternate locales
    }
}

//build Reviews array
if ($is_product_page) {
    $ratingSum = 0;
    $ratingValue = 0;
    $reviewCount = 0;
    $reviewQuery = 'SELECT r.reviews_id, r.customers_name, r.reviews_rating, r.date_added, r.status, rd.reviews_text
                FROM ' . TABLE_REVIEWS . ' r
                LEFT JOIN ' . TABLE_REVIEWS_DESCRIPTION . ' rd ON rd.reviews_id = r.reviews_id
                WHERE products_id = ' . (int)$_GET['products_id'] . '
                AND status = 1
                AND languages_id= ' . $_SESSION['languages_id'] . '
                ORDER BY reviews_rating DESC';
    $reviews = $db->Execute($reviewQuery);
    if (!$reviews->EOF) {
        foreach ($reviews as $review) {
           $reviewsArray[] = [
                'id' => $review['reviews_id'],
                'customersName' => $review['customers_name'],
                'reviewsRating' => $review['reviews_rating'],
                'dateAdded' => (!empty($review['date_added']) ? $review['date_added'] : PLUGIN_SDATA_REVIEW_DEFAULT_DATE), // $review['date_added'] may be NULL
                'reviewsText' => $review['reviews_text']
            ];
            $ratingSum += $review['reviews_rating']; // mc12345678 2022-07-04: If going to omit this review now or in the future, then need to consider this value.
        }
        $reviewCount = count($reviewsArray);
        $ratingValue = round($ratingSum / $reviewCount, 1);
    }
    // if no reviews, make a default review to satisfy testing tool
    if ($reviewCount === 0 && PLUGIN_SDATA_REVIEW_USE_DEFAULT === 'true') {
        $reviewsArray[0] = [
            'id' => 0, // not used
            'customersName' => 'anonymous',
            'reviewsRating' => (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE,
            'dateAdded' => $product_date_added,
            'reviewsText' => ''
        ];
        $ratingValue = (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE;
        $reviewCount = 1;
    }
}
//Merchant Return Policy
//common code block used in attribute-handling option and simple product
if(!empty(PLUGIN_SDATA_RETURNS_POLICY_COUNTRY)) {
    $hasMerchantReturnPolicy = '"hasMerchantReturnPolicy": {
                  "@type": "MerchantReturnPolicy",
                  "returnPolicyCountry": "' . PLUGIN_SDATA_RETURNS_POLICY_COUNTRY . '",
                  "returnPolicyCategory": "' . $returnPolicyCategory[PLUGIN_SDATA_RETURNS_POLICY] . '",' .
        (PLUGIN_SDATA_RETURNS_POLICY === 'finite' ? '
                  "merchantReturnDays": "' . (int)PLUGIN_SDATA_RETURNS_DAYS . '",' : '') . '
                  "returnMethod": "' . $returnMethod[PLUGIN_SDATA_RETURNS_METHOD] . '", ' .
        (PLUGIN_SDATA_RETURNS_FEES === '0' ? '
                  "returnFees": "https://schema.org/FreeReturn"' : '"returnShippingFeesAmount": {
                      "currency" : "' . PLUGIN_SDATA_PRICE_CURRENCY . '",
                      "value": "' . PLUGIN_SDATA_RETURNS_FEES . '"
                  }') . ',
                  "applicableCountry": "' . PLUGIN_SDATA_RETURNS_APPLICABLE_COUNTRY . '"
                  },' . "\n";
} else {
    $hasMerchantReturnPolicy = '';
}
?>
<?php if (PLUGIN_SDATA_SCHEMA_ENABLE === 'true') { ?>
<script title="Structured Data: schemaOrganisation" type="application/ld+json">
{
     "@context": "https://schema.org",
        "@type": "Organization",
          "url": "<?php echo HTTP_SERVER; //root website ?>",
         "logo": "<?php echo PLUGIN_SDATA_LOGO; ?>",
"contactPoint" : [{
            "@type" : "ContactPoint",
        "telephone" : "<?php echo PLUGIN_SDATA_TELEPHONE; ?>",
      "contactType" : "customer service"<?php //a comma may not be necessary here as the following items are optional ?>
<?php echo (PLUGIN_SDATA_AREA_SERVED !== '' ? ",\n" . '       "areaServed" : "' . PLUGIN_SDATA_AREA_SERVED . '"' : ''); //if not declared, assumed worldwide ?>
<?php echo (PLUGIN_SDATA_AVAILABLE_LANGUAGE !== '' ? ",\n" . '"availableLanguage" : "' . PLUGIN_SDATA_AVAILABLE_LANGUAGE . '"' : ''); //if not declared, english is assumed?>
<?php echo "\n                  }],\n"; ?>
<?php if ($sameAs !== '' ) { ?>      "sameAs" : [<?php echo $sameAs . "\n"; ?>
                 ],<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_DUNS !== '') { ?>        "duns" : "<?php echo PLUGIN_SDATA_DUNS; ?>",<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_LEGAL_NAME !== '') { ?>   "legalName" : "<?php echo PLUGIN_SDATA_LEGAL_NAME; ?>",<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_TAXID !== '') { ?>       "taxID" : "<?php echo PLUGIN_SDATA_TAXID; ?>",<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_VATID !== '') { ?>       "vatID" : "<?php echo PLUGIN_SDATA_VATID; ?>",<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_EMAIL !== '') { ?>       "email" : "<?php echo PLUGIN_SDATA_EMAIL; ?>",<?php echo "\n"; } ?>
<?php if (PLUGIN_SDATA_FAX !== '') { ?>     "faxNumber" : "<?php echo PLUGIN_SDATA_FAX; ?>",<?php echo "\n"; } ?>
      "address": {
            "@type": "PostalAddress",
   "streetAddress" : "<?php echo PLUGIN_SDATA_STREET_ADDRESS; ?>",
  "addressLocality": "<?php echo PLUGIN_SDATA_LOCALITY; ?>",
    "addressRegion": "<?php echo PLUGIN_SDATA_REGION; ?>",
       "postalCode": "<?php echo PLUGIN_SDATA_POSTALCODE; ?>",
  "addressCountry" : "<?php echo PLUGIN_SDATA_COUNTRYNAME; ?>"
                 }
}
</script>
<?php if ($breadcrumb_count > 1) { ?>
<script title="Structured Data: schemaBreadcrumb" type="application/ld+json">
{
       "@context": "https://schema.org",
          "@type": "BreadcrumbList",
"itemListElement":
  [
  <?php foreach ($breadcrumb_schema as $key => $value) { ?>
  {
        "@type": "ListItem",
     "position": "<?php echo $value['position']; //does not need to be quoted, but IDE complains ?>",
         "item":
       {
        "@id": "<?php echo $value['id']; ?>",
       "name": <?php echo json_encode($value['name']) . "\n"; ?>
       }
    }<?php if ((int)$key+1 < $breadcrumb_count) echo ','; ?>

<?php }//close foreach ?>
  ]
}
</script>
<?php } //eof breadcrumb ?>
<?php if ($is_product_page) {//product page only ?>
<script title="Structured Data: schemaProduct" type="application/ld+json">
{<?php //structured as per Google example for comparison:https://developers.google.com/search/docs/data-types/product ?>
   "@context": "https://schema.org",
      "@type": "Product",
       "name": <?php echo json_encode(sdata_truncate($product_name, PLUGIN_SDATA_MAX_NAME)); ?>,
      "image": "<?php echo $image; ?>",
"description": <?php echo json_encode(sdata_truncate($description, PLUGIN_SDATA_MAX_DESCRIPTION)); ?>,
        "sku": <?php echo json_encode($product_base_sku); //The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service ?>,
     "weight": <?php echo json_encode($weight . TEXT_PRODUCT_WEIGHT_UNIT); ?>,
<?php
if ($product_base_mpn !== '') {//The Manufacturer Part Number (MPN) of the product
    echo '        "mpn": ' . json_encode($product_base_mpn) . ",\n";
    }
if ($product_base_gtin !== '') {//The Manufacturer-supplied standard international code
    echo '       "gtin": ' . json_encode($product_base_gtin) . ",\n";
}
if ($product_base_productID !== '') {//a non-standard code according to Google, but a real product identifier (ISBN, EAN) according to Schema. Default is products_model, so if not being used, this will not be created.
    echo '  "productID": ' . json_encode($product_base_productID) . ",\n";
}
if ($product_base_gpc !== '') {//google product category
    echo '  "googleProductCategory": "' . $product_base_gpc . '"' . ",\n";
    echo '  "google_product_category": "' . $product_base_gpc . '"' . ",\n";//belt and braces
} ?>
      "brand": {
              "@type" : "Brand",
               "name" : <?php echo json_encode($manufacturer_name) . "\n"; ?>
                },
  "category" : <?php echo json_encode($category_name); //impossible to find conclusive information on this, but it is NOT google_product_category number/it must be text ?>,
<?php if ($product_attributes) {// there is some field duplication between attributes, default and simple product...but having the [ around the multiple offers when attributes-stock is handled complicates the code so leave separate for easier maintenance. Need to test on all three scenarios: simple (no attributes) / attributes - Zen Cart default / attributes - stock handled by 3rd-party plugin
        switch ($attribute_stock_handler) {
            case ('posm'): ?>
"__comment" : "attribute stock handling:<?php echo $attribute_stock_handler; ?>",
    "offers" : [
    <?php $i = 0;$attributes_count=count($product_attributes);foreach($product_attributes as $index=>$product_attribute) { $i++; ?>
            {
            <?php if (!empty($hasMerchantReturnPolicy)) {
                echo $hasMerchantReturnPolicy;
            } ?>
            "@type" : "Offer",
<?php if (!empty($product_attribute['sku'])) {?>
                   "sku" : "<?php echo $product_attribute['sku']; ?>",
<?php } ?>
<?php if (!empty($product_attribute['mpn'])) {?>
                   "mpn" : "<?php echo $product_attribute['mpn']; ?>",
<?php } ?>
<?php if (!empty($product_attribute['gtin'])) {?>
                  "gtin" : "<?php echo $product_attribute['gtin']; ?>",
<?php } ?>
                 "price" : "<?php echo $product_attribute['price']; ?>",
<?php if (!empty($product_attribute['weight'])) {//TODO temporary fix for missing attribute in POSM handling ?>
                "weight" : "<?php echo ($weight + $product_attribute['weight'] > 0 ? $weight + $product_attribute['weight'] : $weight) . TEXT_PRODUCT_WEIGHT_UNIT; //if a subtracted attribute weight is less than zero, use base weight ?>",
<?php } ?>
         "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>",
          "availability" : "<?php echo $product_attribute['stock'] > 0 ? $itemAvailability['InStock'] : $oosItemAvailability; ?>",
    <?php if ($product_attribute['stock'] < 1 && $backPreOrderDate !== '') { ?> "availability_date" : "<?php echo $backPreOrderDate; ?>",
    <?php } ?>
   "priceValidUntil" : "<?php echo date('Y') . '-12-31'; //e.g. 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
                    "url": "<?php echo $url; ?>"}<?php if ($i < $attributes_count) { echo ",\n    "; } else {echo "\n";}?>
<?php } ?>
         ]
<?php break;

            default://'default' Zen Cart attribute prices only (no sku/mpn/gtin) ?>
            "__comment" : "attribute stock handling default:<?php echo $attribute_stock_handler; ?>",
               "offers" : {
               <?php if (!empty($hasMerchantReturnPolicy)) {
                echo $hasMerchantReturnPolicy;
            } ?>
                       "url": "<?php echo $url; ?>",
<?php if ($attribute_lowPrice === $attribute_highPrice) { //or if price not set by attributes, this is already set to base price ?>
                    "@type" : "Offer",
                    "price" : "<?php echo $attribute_lowPrice; ?>",
                <?php } else { ?>
                    "@type" : "AggregateOffer",
<?php } ?>
                 "lowPrice" : "<?php echo $attribute_lowPrice; ?>",
                "highPrice" : "<?php echo $attribute_highPrice; ?>",
               "offerCount" : "<?php echo $offerCount; //required for AggregateOffer. Not zero ?>",
            "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>",
          "priceValidUntil" : "<?php echo date('Y') . '-12-31'; //e.g. 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
            "itemCondition" : "https://schema.org/<?php echo $itemCondition[PLUGIN_SDATA_FOG_PRODUCT_CONDITION]; ?>",
         "availability" : "<?php echo ($product_base_stock > 0 ? $itemAvailability['InStock'] : $oosItemAvailability); ?>",
    <?php if ($backPreOrderDate !== '') { ?>"availability_date" : "<?php echo $backPreOrderDate; ?>",
    <?php } ?>          "seller" : <?php echo json_encode(STORE_NAME); //json_encode adds external quotes as the other entries"?>,
         "deliveryLeadTime" : "<?php echo ($product_base_stock > 0 ? PLUGIN_SDATA_DELIVERYLEADTIME : PLUGIN_SDATA_DELIVERYLEADTIME_OOS); ?>",
              "itemOffered" : <?php echo json_encode($product_name); ?>,
<?php if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') { ?>
           "eligibleRegion" : "<?php echo PLUGIN_SDATA_ELIGIBLE_REGION ;?>",<?php echo "\n";
} ?>
    "acceptedPaymentMethod" : {
                       "@type" : "PaymentMethod",
                        "name" : [<?php echo $PaymentMethods; ?>]
                              }
                          }
<?php }//close attributes switch-
} else { //simple product (no attributes) ?>
            "offers" :     {
              <?php if (!empty($hasMerchantReturnPolicy)) {
                  echo $hasMerchantReturnPolicy;
    } ?>
                "@type" : "Offer",
                "price" : "<?php echo $product_base_displayed_price; ?>",
                   "url": "<?php echo $url; ?>",
        "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>",
      "priceValidUntil" : "<?php echo date('Y') . '-12-31'; //e.g. 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
        "itemCondition" : "https://schema.org/<?php echo $itemCondition[PLUGIN_SDATA_FOG_PRODUCT_CONDITION]; ?>",
         "availability" : "<?php echo ($product_base_stock > 0 ? $itemAvailability['InStock'] : $oosItemAvailability); ?>",
    <?php if ($backPreOrderDate !== '') { ?>"availability_date" : "<?php echo $backPreOrderDate; ?>",
    <?php } ?>           "seller" : <?php echo json_encode(STORE_NAME); //json_encode adds external quotes as the other entries"?>,
     "deliveryLeadTime" : "<?php echo ($product_base_stock > 0 ? PLUGIN_SDATA_DELIVERYLEADTIME : PLUGIN_SDATA_DELIVERYLEADTIME_OOS); ?>",
          "itemOffered" : <?php echo json_encode($product_name); ?>,
<?php if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') { ?>
  "eligibleRegion" : "<?php echo PLUGIN_SDATA_ELIGIBLE_REGION; ?>",
  <?php } ?>
"acceptedPaymentMethod" : {
                  "@type" : "PaymentMethod",
                   "name" : [<?php echo $PaymentMethods; ?>]
                          }
               }
<?php } ?>
<?php if ( $reviewCount > 0 ) { //do not bother if no reviews at all. Note best/worstRating is for the max and min rating used in this review system. Default is 1 and 5 so no need to be declared ?>
,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $ratingValue; //average rating based on all reviews ?>",
    "reviewCount": "<?php echo $reviewCount; ?>"
  },
  "review" : [
  <?php for ($i = 0, $n = count($reviewsArray); $i<$n; $i ++) { ?>
  {
    "@type" : "Review",
    "author" : {
      "@type" : "Person",
      "name" : <?php echo json_encode(strtok($reviewsArray[$i]['customersName'], ' ')); //to use only the forename, encoded, does NOT need enclosing quotation marks ?>
    },
    "reviewBody" : <?php echo json_encode($reviewsArray[$i]['reviewsText']); //added json_encode to catch quotation marks and pesky accents etc., does NOT need enclosing quotation marks ?>,
    "datePublished" : "<?php echo substr($reviewsArray[$i]['dateAdded'], 0, 10); ?>",
    "reviewRating" : {
      "@type" : "Rating",
      "ratingValue" : "<?php echo $reviewsArray[$i]['reviewsRating']; ?>"
      }
    }<?php if ($i+1 !== $n) { echo ','; } ?>
  <?php } ?>
  ]
<?php } //if no reviews, aggregateRating makes no sense ?>
}
</script>
<?php } //eof Product Schema
}//eof Schema enabled ?>
<?php if (PLUGIN_SDATA_FOG_ENABLE === 'true') {?>
<!-- Facebook structured data general-->
<?php if (PLUGIN_SDATA_FOG_APPID !== '') { ?>
<meta property="fb:app_id" content="<?php echo PLUGIN_SDATA_FOG_APPID; ?>">
<?php } ?>
<?php if (PLUGIN_SDATA_FOG_ADMINID !== '') { ?>
<meta property="fb:admins" content="<?php echo PLUGIN_SDATA_FOG_ADMINID; ?>">
<?php } ?>
<meta property="og:title" content="<?php echo $title; ?>">
<meta property="og:site_name" content="<?php echo STORE_NAME; ?>">
<meta property="og:url" content="<?php echo $url; ?>">
<?php if (!empty($locale)) { echo '<meta property="og:locale" content="' . $locale . '">';
if (count($locales_array) > 0) {
foreach($locales_array as $key=>$value){ ?>
<meta property="og:locale:alternate" content="<?php echo $value; ?>">
<?php }}} ?>
<?php $image = ($image_default ? $image_default_facebook : $image); ?>
<?php if ($debug_sd) {echo __LINE__ . ' $image_default=' . $image_default . '<br>';} ?>
<meta property="og:image" content="<?php echo $image; ?>">
<meta property="og:image:url" content="<?php echo $image; ?>">
<?php
    if (is_readable(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image))) {
      $image_info = @getimagesize(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image));
//log the problem for correction
if ($image_info === false) {
    error_log(__FILE__ . ":getimagesize($image) returned FALSE: image is corrupt");
    $image = DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE;
    $image_info = getimagesize(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image)); ?>
<!-- ERROR: image is corrupt: see debug logs -->
<?php } ?>
<meta property="og:image:alt" content="<?php echo htmlentities($image_alt, ENT_QUOTES, CHARSET, false); ?>">
<meta property="og:image:type" content="<?php echo $image_info['mime']; ?>">
<meta property="og:image:width" content="<?php echo $image_info[0]; ?>">
<meta property="og:image:height" content="<?php echo $image_info[1]; ?>">
<?php } ?>
<meta property="og:description" content="<?php echo htmlentities($description); ?>">
    <?php if ($facebook_type !== 'product') { ?>
<meta property="og:type" content="<?php echo PLUGIN_SDATA_FOG_TYPE_SITE; ?>">
    <?php if (PLUGIN_SDATA_STREET_ADDRESS !== '') { ?>
<meta property="business:contact_data:street_address" content="<?php echo PLUGIN_SDATA_STREET_ADDRESS; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_LOCALITY !== '') { ?>
<meta property="business:contact_data:locality" content="<?php echo PLUGIN_SDATA_LOCALITY; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_REGION !== '') { ?>
<meta property="business:contact_data:region" content="<?php echo PLUGIN_SDATA_REGION; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_POSTALCODE !== '') { ?>
<meta property="business:contact_data:postal_code" content="<?php echo PLUGIN_SDATA_POSTALCODE; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_COUNTRYNAME !== '') { ?>
<meta property="business:contact_data:country_name" content="<?php echo PLUGIN_SDATA_COUNTRYNAME; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_EMAIL !== '') { ?>
<meta property="business:contact_data:email" content="<?php echo PLUGIN_SDATA_EMAIL; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_TELEPHONE !== '') { ?>
<meta property="business:contact_data:phone_number" content="<?php echo PLUGIN_SDATA_TELEPHONE; ?>">
    <?php } ?>
    <?php if (PLUGIN_SDATA_FAX !== '') { ?>
<meta property="business:contact_data:fax_number" content="<?php echo PLUGIN_SDATA_FAX; ?>">
    <?php } ?>
<meta property="business:contact_data:website" content="<?php echo HTTP_SERVER; ?>">
<!-- eof Facebook structured data general-->
<?php } else { ?>
<!-- Facebook structured data for product-->
<meta property="og:type" content="<?php echo trim(PLUGIN_SDATA_FOG_TYPE_PRODUCT); ?>">
<meta property="product:availability" content="<?php if ($product_base_stock > 0) { ?>instock<?php } ?><?php if ($product_base_stock < 1) { ?>pending<?php } ?>">
<meta property="product:brand" content="<?php echo $manufacturer_name; ?>">
<meta property="product:category" content="<?php echo htmlentities($category_name); ?>">
<meta property="product:condition" content="<?php echo PLUGIN_SDATA_FOG_PRODUCT_CONDITION; ?>">
<?php if ($product_base_mpn !== '') {
                echo '<meta property="product:mfr_part_no" content="' . $product_base_mpn . '">' . "\n";
            } ?>
<meta property="product:price:amount" content="<?php echo $product_base_displayed_price; ?>">
<meta property="product:price:currency" content="<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>">
<meta property="product:product_link" content="<?php echo $url; ?>">
<meta property="product:retailer" content="<?php echo PLUGIN_SDATA_FOG_APPID; ?>">
<meta property="product:retailer_category" content="<?php echo htmlentities($category_name); ?>">
<meta property="product:retailer_part_no" content="<?php echo $product_base_sku; ?>">
<!-- eof Facebook structured data -->
<?php } }//end facebook enabled  ?>
<?php if (PLUGIN_SDATA_TWITTER_CARD_ENABLE === 'true') { ?>
<!-- Twitter Card markup -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="<?php echo PLUGIN_SDATA_TWITTER_USERNAME; ?>">
<meta name="twitter:title" content="<?php echo $title; ?>">
<meta name="twitter:description" content="<?php echo htmlentities($description); ?>">
<?php $image = ($image_default ? $image_default_twitter : $image); ?>
<meta name="twitter:image" content="<?php echo $image; ?>">
<meta name="twitter:image:alt" content="<?php echo htmlentities($image_alt, ENT_QUOTES, CHARSET, false); ?>">
<meta name="twitter:url" content="<?php echo htmlentities($url, ENT_COMPAT, CHARSET, false); ?>">
<meta name="twitter:domain" content="<?php echo HTTP_SERVER; ?>">
<!-- eof Twitter Card markup -->
<?php } //end of Twitter enabled ?>
<?php //google+ markup
if (PLUGIN_SDATA_GOOGLE_PUBLISHER !== '') { ?>
<!-- Google+-->
<link href="<?php echo PLUGIN_SDATA_GOOGLE_PUBLISHER; ?>" rel="publisher">
<!-- eof Google+--><?php } //eof Google+ ?>
