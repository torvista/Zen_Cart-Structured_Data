<?php
/* This file MUST be loaded by html <head> since it uses meta tags.
 * DO NOT LET YOUR IDE RE-FORMAT THE CODE STRUCTURE: it is structured so the html seen in Developer Tools Inspector (Chrome) is readable and the parentheses line up.
  *
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
/** directives for phpStorm code inspector
 ** @var queryFactory $db
 ** @var sniffer $sniffer
 ** @var $canonicalLink
 ** @var $current_page
 ** @var $current_page_base
 ** @var $product_id
 */
if (!defined('PLUGIN_SDATA_ENABLE') || PLUGIN_SDATA_ENABLE !== 'true') {
  return;
}

//new defines to add to installer one day
define('PLUGIN_SDATA_REVIEW_USE_DEFAULT', 'true'); // if no product review, use a default value to stop Google warnings
define('PLUGIN_SDATA_REVIEW_DEFAULT_VALUE', '3'); // avg. rating (when no product reviews exist)
define('PLUGIN_SDATA_MAX_DESCRIPTION', 5000); // maximum characters allowed (Google)
define('PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY', ''); // fallback/default Google category ID (up to 6 digits). Used if a product does not have a specific category defined https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.xls
//eg '5613'	= Vehicles & Parts, Vehicle Parts & Accessories
define('PLUGIN_SDATA_DEFAULT_WEIGHT', '0.3'); // fallback weight if product weight in database is not set
//not used define ('PLUGIN_SDATA_NATIVE_URL', true); // if a url rewriter is in use, the native url is replaced by a more "friendly" one (eg. www.shop.com/products/widget1). If this option is set to true, the native url (www.shop.com/index.php?main_page=product_info&cPath=1_4&products_id=1) is always used.

if (defined('PLUGIN_SDATA_PRICE_CURRRENCY')) {//sic: correct old typo
    $db->Execute("UPDATE `configuration` SET `configuration_key`= 'PLUGIN_SDATA_PRICE_CURRENCY' WHERE `configuration_key`= 'PLUGIN_SDATA_PRICE_CURRRENCY'");
}

$debug_sd = false; // set to true (boolean) to display debugging info. Changes from the gods are imposed regularly, so I've left a lot of ugly debug output available.

//defaults (subsequently overwritten), defined to prevent php notices
$description = '';
$title = '';
$image = '';
$image_alt = '';
$image_default = false;
$facebook_type = 'business.business';
$key = ''; //only to keep IDE happy
$url = $canonicalLink; //may be native or friendly if rewriter in use
/*cludged solution, don't like it so not implemented/incomplete. Should catch parameters passed to notify_sefu_intercept which url rewriter should be using
if(PLUGIN_SDATA_NATIVE_URL === true) { //always use the native url
    $url = trim(HTTP_SERVER . DIR_WS_CATALOG . ($current_page === 'index' ? '' : 'index.php?main_page=' . $current_page . '&' . $_SERVER['QUERY_STRING']), '&');
}*/
//product condition mapping for Schema
$itemCondition_array = ['new' => 'NewCondition', 'used' => 'UsedCondition', 'refurbished' => 'RefurbishedCondition'];

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

//only used for debugging
if (!function_exists('mv_printVar')) {
    /**
     * @param $a
     */
    function mv_printVar($a)
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
}
if ($debug_sd) {
    echo 'PLUGIN_SDATA_FOG_DEFAULT_IMAGE=' . PLUGIN_SDATA_FOG_DEFAULT_IMAGE . '<br>';
    echo 'PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE=' . PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE . '<br>';
    echo 'PLUGIN_SDATA_LOGO=' . PLUGIN_SDATA_LOGO . '<br>';
    echo '$image_default_facebook=' . $image_default_facebook . '<br>';
    echo '$image_default_twitter=' . $image_default_twitter . '<br>';
}

// mc12345678, this appeared to only cover one product type, not all of them. Corrected 2022-07-04
$is_product_page = (substr($current_page_base, -5) === '_info' && (!empty($_GET['products_id']) && zen_products_lookup($_GET['products_id'], 'products_status') === '1')
    && zen_get_info_page($_GET['products_id']) === $current_page_base);
if ($is_product_page) {//product page only
    if ($debug_sd) {
        echo __LINE__ . ' is product page<br>';
    }
    //get product info

    $sql = 'SELECT p.products_id, p.products_quantity, p.products_model, p.products_image, p.products_price, p.products_date_added, p.products_weight, p.products_tax_class_id, p.products_priced_by_attribute, pd.products_name, pd.products_description
           FROM ' . TABLE_PRODUCTS . ' p, ' . TABLE_PRODUCTS_DESCRIPTION . ' pd
           WHERE p.products_id = ' . (int)$_GET['products_id'] . '
           AND pd.products_id = p.products_id
           AND pd.language_id = ' . (int)$_SESSION['languages_id'];
    $product_info = $db->Execute($sql);

    $product_id = (int)$product_info->fields['products_id'];
    $product_name = $product_info->fields['products_name'];
    $description = $product_info->fields['products_description'];//variable used in twitter for categories & products
    $title = htmlspecialchars(STORE_NAME . ' - ' . $product_info->fields['products_name']);
    $weight = (float)($product_info->fields['products_weight'] === '0' ? PLUGIN_SDATA_DEFAULT_WEIGHT : $product_info->fields['products_weight']);
    $tax_class_id = $product_info->fields['products_tax_class_id'];
    $product_base_displayed_price = round(zen_get_products_actual_price($product_id) * (1 + zen_get_tax_rate($tax_class_id) / 100),
        2);//shown price with tax, decimal point (not comma), two decimal places.
    $product_date_added = $product_info->fields['products_date_added'];
    $manufacturer_name = zen_get_products_manufacturers_name((int)$_GET['products_id']);
    $product_base_stock = $product_info->fields['products_quantity'];

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
//bof ******************CUSTOM CODE for extra product fields for mpn, ean and google product category***********************/

//torvista: my site uses boilerplate texts for product descriptions ************
//Product Descriptions
    if (function_exists('mv_get_boilerplate')) {
        $description = mv_get_boilerplate($description, $product_id);
    }
//****************************************************
//sku/mpn/gtin, price, stock may all vary per attribute
//Attributes handling info: https://www.schemaapp.com/newsletter/schema-org-variable-products-productmodels-offers/#
    $product_attributes = false;
    $attribute_stock_handler = 'not_defined';
    $attribute_lowPrice = 0;
    $attribute_highPrice = 0;
    $offerCount = 1; //but what the hell is it? Sum of all variants in stock or just the number of variants? Should not be zero.

    if (zen_has_product_attributes($product_id)) {
        $product_attributes = [];
        $attribute_prices = [];

// Get attribute info
        $sql = "SELECT patrib.products_attributes_id, patrib.options_id, patrib.options_values_id, patrib.options_values_price, patrib.products_attributes_weight, patrib.products_attributes_weight_prefix, popt.products_options_name, poptv.products_options_values_name
                    FROM " . TABLE_PRODUCTS_OPTIONS . " popt
                    LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " patrib ON (popt.products_options_id = patrib.options_id)
                    LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_VALUES . " poptv ON (poptv.products_options_values_id = patrib.options_values_id AND poptv.language_id = popt.language_id)
                    WHERE patrib.products_id = " . $product_id . "
                    AND popt.language_id = " . (int)$_SESSION['languages_id'] . "
                    ORDER BY popt.products_options_name, poptv.products_options_values_name";
        $results = $db->Execute($sql);

        foreach ($results as $attribute) {
            if (zen_get_attributes_valid($product_id, $attribute['options_id'], $attribute['options_values_id'])) {//skip "display only"
                $product_attributes[$attribute['products_attributes_id']]['option_name_id'] = $attribute['options_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_name'] = $attribute['products_options_name'];
                $product_attributes[$attribute['products_attributes_id']]['option_value_id'] = $attribute['options_values_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_value'] = $attribute['products_options_values_name'];
                $product_attributes[$attribute['products_attributes_id']]['price'] = zen_get_products_price_is_priced_by_attributes($product_id) ? $attribute['options_values_price']
                    : $product_base_displayed_price;
                $attribute_prices[] = $product_attributes[$attribute['products_attributes_id']]['price'];
                if ($attribute['products_attributes_weight'] === '0') {
                    $product_attributes[$attribute['products_attributes_id']]['weight'] = 0;
                } else {
                    $product_attributes[$attribute['products_attributes_id']]['weight'] = (float)(($attribute['products_attributes_weight_prefix'] === '-' ? '-' : '')
                        . $attribute['products_attributes_weight']);
                }
            }
        }
        $attribute_lowPrice = min($attribute_prices);
        $attribute_highPrice = max($attribute_prices);

        if ($debug_sd) {
            echo __LINE__ . ' $attribute_lowPrice=' . $attribute_lowPrice . ' | $attribute_highPrice=' . $attribute_highPrice . '<br>count($product_attributes)=' . count($product_attributes);
            mv_printVar($product_attributes);
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
        Each shop must add code from where to retrieve)the values to load into mpn/gtin. In case I have used ean.
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

            case (defined('POSM_ENABLE') && POSM_ENABLE === 'true'):
                //todo bof hack to break to default, when dependant attributes/more than one attribute
                $option_ids = [];
                foreach ($product_attributes as $key => $product_attribute) {
                    $option_ids[] = $product_attribute['option_name_id'];
                }
                $option_id_min = min($option_ids);
                $option_id_max = max($option_ids);
                if ($option_id_min !== $option_id_max) {//there are two or more option names....run away!
                    $attribute_stock_handler = 'posm_multiple'; //todo
                    break;
                }
                //eof hack

                //using "Products Options Stock Manager": https://vinosdefrutastropicales.com/index.php?main_page=product_info&cPath=2_7&products_id=46
                if ($debug_sd) {
                    echo __LINE__ . ' Attributes: using POSM<br>';
                }

                if (is_pos_product($product_id)) {//POSM manages stock of this product
                    $attribute_stock_handler = 'posm';
                    $total_attributes_stock = 0;
                    foreach ($product_attributes as $key => $product_attribute) {
                        //some defaults in case there is no POSM entry despite an attribute existing
                        $product_attributes[$key]['stock'] = 0;
                        $product_attributes[$key]['sku'] = $product_base_productID;
                        $product_attributes[$key]['mpn'] = $product_base_mpn;
                        $product_attributes[$key]['gtin'] = $product_base_gtin;

                        //copied from observer function getOptionsStockRecord as it's a Protected function
                        $hash = generate_pos_option_hash($product_id, [$product_attribute['option_name_id'] => $product_attribute['option_value_id']]);

                        $posm_record = $db->Execute("SELECT * FROM " . TABLE_PRODUCTS_OPTIONS_STOCK . " WHERE products_id = $product_id AND pos_hash = '$hash' LIMIT 1", false, false, 0, true);
                        /* example output if extra fields have been added:
                        ALTER TABLE `products_options_stock` ADD `pos_mpn` VARCHAR(32) NOT NULL DEFAULT '' AFTER `pos_model`;
                        ALTER TABLE `products_options_stock` ADD `pos_ean` VARCHAR(13) NOT NULL DEFAULT '' AFTER `pos_mpn`;
                        (
                            [pos_id] => 2737
                            [products_id] => 115
                            [pos_name_id] => 2
                            [products_quantity] => 1
                            [pos_hash] => 456b69e6df96dd253fc746afd1c3d04d
                            [pos_model] => HT-1156
                            [pos_mpn] =>SH-A01
                            [pos_ean] =>1234567891234
                            [pos_date] => 0001-01-01
                            [last_modified] => 2020-06-19 14:48:16
                        )
                         */
                        if ($posm_record->EOF) {
                            continue;
                        }
                        $product_attributes[$key]['stock'] = $posm_record->fields['products_quantity'];
                        $product_attributes[$key]['sku'] = $posm_record->fields['pos_model'];//as per individual shop
                        $total_attributes_stock += $posm_record->fields['products_quantity'];

                        //CUSTOM CODING REQUIRED***************************************
                        if ($sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_mpn') && $sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_ean')) {
                            //$product_attributes[$key]['mpn'] = $product_attributes[$key]['option_value'];//as per individual shop
                            $product_attributes[$key]['mpn'] = $posm_record->fields['pos_mpn'];//as per individual shop
                            $product_attributes[$key]['gtin'] = $posm_record->fields['pos_ean'];//as per individual shop
                        }
                        //eof CUSTOM CODING REQUIRED***********************************
                    }
                    $offerCount = (max($product_base_stock + $total_attributes_stock, 1)); //maybe, hard to find a definition
                }
                break;

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
        mv_printVar($product_attributes);
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
} elseif (isset($_GET['cPath'])) {//NOT a product page
    if ($debug_sd) {
        echo __LINE__ . ' is NOT product page<br>';
    }
    $cPath_array = explode('_', $_GET['cPath']);
    $category_id = end($cPath_array);
    reset($cPath_array);
    $category_name = zen_get_category_name($category_id, (int)$_SESSION['languages_id']); // ZC158 does not need language parameter8
    if ($category_name !== '') { //a valid category
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
        $product_category_name = $category_name;//used for twitter title, changes depending if page is product or category
        $image_alt = $category_name;
        $facebook_type = 'product.group';
        $title = META_TAG_TITLE;
    }
} else {//some other page - not product or category, maybe a bad cPath https://github.com/zencart/zencart/issues/2903
    if ($debug_sd) {
        echo __LINE__ . ' is "Other" page<br>';
    }

    $image_default = true;
    $breadcrumb_this_page = $breadcrumb->_trail[count($breadcrumb->_trail) - 1]['title'] ?? '';
    $image_alt = $breadcrumb_this_page;
    $title = META_TAG_TITLE;
    //$title = $breadcrumb_this_page;
    $description = META_TAG_DESCRIPTION;
    $facebook_type = 'business.business';
}

//$description could be null from META_TAG_DESCRIPTION
if (empty($description)) {
    $description = '';
} else {
    //clean $description
    $description = htmlentities(strip_tags(trim($description)), ENT_COMPAT, CHARSET);//remove tags
    $description = str_replace(["\r\n", "\n", "\r"], '', $description);//remove LF, CR
    $description = preg_replace('/\s+/', ' ', $description);//remove multiple spaces
    $description = zen_trunc_string($description, PLUGIN_SDATA_MAX_DESCRIPTION);
}
//build sameAs list
$sameAs_array = explode(', ', PLUGIN_SDATA_SAMEAS);
array_push($sameAs_array, PLUGIN_SDATA_FOG_PAGE, PLUGIN_SDATA_TWITTER_PAGE, PLUGIN_SDATA_GOOGLE_PUBLISHER);
$contact_us = $_GET['main_page'] !== 'contact_us' ? HTTP_SERVER . DIR_WS_CATALOG . 'index.php?main_page=contact_us' : '';
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
        $reviewsArray = [];
        foreach ($reviews as $review) {
            $reviewsArray[] = [
                'reviewId' => $review['reviews_id'],
                'customerName' => $review['customers_name'],
                'reviewRating' => $review['reviews_rating'],
                'dateAdded' => $review['date_added'],
                'reviewText' => $review['reviews_text']
            ];
            $ratingSum += $review['reviews_rating']; // mc12345678 2022-07-04: If going to omit this review now or in the future, then need to consider this value.
        }
        $reviewCount = count($reviewsArray);
        /*            foreach ($reviewsArray as $row) { // This is an increase of O of the runtime.
                        $ratingSum += $row['reviewRating'];
                    }*/
        $ratingValue = round($ratingSum / $reviewCount, 1);
    }
    if ($reviewCount === 0 && PLUGIN_SDATA_REVIEW_USE_DEFAULT === 'true') {
        $reviewsArray[] = [
            'reviewId' => 0, // not used
            'customerName' => 'anonymous',
            'reviewRating' => (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE,
            'dateAdded' => $product_date_added,
            'reviewText' => ''
        ];
        $ratingValue = (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE;
        $reviewCount = 1;
    }
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
<?php if (isset($breadcrumb) && is_object($breadcrumb)) { ?>
<script title="Structured Data: schemaBreadcrumb" type="application/ld+json">
{
       "@context": "https://schema.org",
          "@type": "BreadcrumbList",
"itemListElement": [
<?php
                foreach ($breadcrumb as $key => $value) {
                    for ($i = 0, $n = count($value); $i < $n; $i++) {
                        // If there is no title, then don't include in the breadcrumb list.
                        if (!(isset($value[$i]['title']) && zen_not_null($value[$i]['title']))) {//if non-existent url used, title is null: php notice) 
                            continue;
                        }

                        // If at least one breadcrumb has been added, then the next should be separated by a comma.
                        if (!empty($min_one)) {
                            echo ",\n";
                        } else {
                            // This is now the first breadcrumb, any future should be separated from the previous.
                            $min_one = true;
                        }
                        ?>
      {
      "@type": "ListItem",
   "position": "<?php echo $i + 1; //does not need to be quoted, but IDE complains ?>",
       "item": {
           "@id": "<?php echo $value[$i]['link']; ?>",
          "name": <?php echo json_encode($value[$i]['title']) . "\n"; ?>
               }
       }
<?php //} //close isset
   }//close for
 }//close foreach ?>

                    ]
}
</script>
<?php } //eof breadcrumb ?>
<?php if ($is_product_page) {//product page only ?>
<script title="Structured Data: schemaProduct" type="application/ld+json">
{<?php //structured as per Google example for comparison:https://developers.google.com/search/docs/data-types/product ?>
   "@context": "https://schema.org",
      "@type": "Product",
       "name": <?php echo json_encode($product_name); ?>,
      "image": "<?php echo $image; ?>",
"description": <?php echo json_encode($description); ?>,
        "sku": <?php echo json_encode($product_base_sku); //The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service ?>,
     "weight": <?php echo json_encode($weight . TEXT_PRODUCT_WEIGHT_UNIT); ?>,
<?php
if ($product_base_mpn !== '') {//The Manufacturer Part Number (MPN) of the product
    echo '        "mpn": ' . json_encode($product_base_mpn) . ",\n";
    }
if ($product_base_gtin !== '') {//The Manufacturer-supplied standard international code
    echo '       "gtin": ' . json_encode($product_base_gtin) . ",\n";
}
if ($product_base_productID !== '') {//a non-standard code
    echo '  "productID": ' . json_encode($product_base_productID) . ",\n";
}
if ($product_base_gpc !== '') {//google product category
    echo '  "googleProductCategory": "' . $product_base_gpc . '"' . ",\n";
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
    <?php $i = 0;$attributes_count=count($product_attributes);foreach($product_attributes as $index=>$product_attribute) { $i++;?>
            {"@type" : "Offer",
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
                "weight" : "<?php echo ($weight + $product_attribute['weight'] > 0 ? $weight + $product_attribute['weight'] : $weight) . TEXT_PRODUCT_WEIGHT_UNIT; //if a subtracted attribute weight is less than zero, use base weight ?>",
         "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>",
          "availability" : "<?php echo $product_attribute['stock'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder'; ?>",
       "priceValidUntil" : "<?php echo date("Y") . '-12-31'; //eg 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
                    "url": "<?php echo $url; ?>"}<?php if ($i < $attributes_count) { echo ",\n    "; } else {echo "\n";}?>
<?php } ?>
         ],
<?php break;

            default://'default' Zen Cart attribute prices only (no sku/mpn/gtin) ?>
            "__comment" : "attribute stock handling default:<?php echo $attribute_stock_handler; ?>",
               "offers" : {
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
          "priceValidUntil" : "<?php echo date("Y") . '-12-31'; //eg 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
            "itemCondition" : "https://schema.org/<?php echo $itemCondition_array[PLUGIN_SDATA_FOG_PRODUCT_CONDITION]; ?>",
             "availability" : "<?php echo ($product_base_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder'); ?>",
                   "seller" : <?php echo json_encode(STORE_NAME); //json_encode adds external quotes as the other entries"?>,
         "deliveryLeadTime" : "<?php echo ($product_base_stock > 0 ? PLUGIN_SDATA_DELIVERYLEADTIME : PLUGIN_SDATA_DELIVERYLEADTIME_OOS); ?>",
              "itemOffered" : <?php echo json_encode($product_name); ?>,
<?php if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') { ?>
           "eligibleRegion" : "<?php echo PLUGIN_SDATA_ELIGIBLE_REGION ;?>",<?php echo "\n";
} ?>
    "acceptedPaymentMethod" : {
                       "@type" : "PaymentMethod",
                        "name" : [<?php echo $PaymentMethods; ?>]
                              }
                          },
<?php }//close switch
} else { //simple product (no attributes) ?>
            "offers" :     {
                "@type" : "Offer",
                "price" : "<?php echo $product_base_displayed_price; ?>",
                   "url": "<?php echo $url; ?>",
        "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>",
      "priceValidUntil" : "<?php echo date("Y") . '-12-31'; //eg 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
        "itemCondition" : "https://schema.org/<?php echo $itemCondition_array[PLUGIN_SDATA_FOG_PRODUCT_CONDITION]; ?>",
         "availability" : "<?php echo ($product_base_stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder'); ?>",
               "seller" : <?php echo json_encode(STORE_NAME); //json_encode adds external quotes as the other entries"?>,
     "deliveryLeadTime" : "<?php echo ($product_base_stock > 0 ? PLUGIN_SDATA_DELIVERYLEADTIME : PLUGIN_SDATA_DELIVERYLEADTIME_OOS); ?>",
          "itemOffered" : <?php echo json_encode($product_name); ?>,
<?php if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') { ?>
  "eligibleRegion" : "<?php echo PLUGIN_SDATA_ELIGIBLE_REGION; ?>",
  <?php } ?>
"acceptedPaymentMethod" : {
                  "@type" : "PaymentMethod",
                   "name" : [<?php echo $PaymentMethods; ?>]
                          }
               },
<?php } ?>
<?php if ( $reviewCount > 0 ) { //do not bother if no reviews at all. Note note best/worstRating is for the max and min rating used in this review system. Default is 1 and 5 so no need to be declared ?>
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
      "name" : <?php echo json_encode(strtok($reviewsArray[$i]['customerName']," ")); //to use only the forename, encoded, does NOT need enclosing quotation marks ?>
    },
    "reviewBody" : <?php echo json_encode($reviewsArray[$i]['reviewText']); //added json_encode to catch quotation marks and pesky accents etc., does NOT need enclosing quotation marks ?>,
    "datePublished" : "<?php echo substr($reviewsArray[$i]['dateAdded'], 0, 10); ?>",
    "reviewRating" : {
      "@type" : "Rating",
      "ratingValue" : "<?php echo $reviewsArray[$i]['reviewRating']; ?>"
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
<meta property="fb:app_id" content="<?php echo PLUGIN_SDATA_FOG_APPID; ?>" />
<?php } ?>
<?php if (PLUGIN_SDATA_FOG_ADMINID !== '') { ?>
<meta property="fb:admins" content="<?php echo PLUGIN_SDATA_FOG_ADMINID; ?>" />
<?php } ?>
<meta property="og:title" content="<?php echo $title; ?>" />
<meta property="og:site_name" content="<?php echo STORE_NAME; ?>" />
<meta property="og:url" content="<?php echo $url; ?>" />
<?php if (!empty($locale)) { echo '<meta property="og:locale" content="' . $locale . '" />';
if (count($locales_array) > 0) {
foreach($locales_array as $key=>$value){ ?>
<meta property="og:locale:alternate" content="<?php echo $value; ?>" />
<?php }}} ?>
<?php $image = ($image_default ? $image_default_facebook : $image); ?>
<?php if ($debug_sd) {echo __LINE__ . ' $image_default=' . $image_default . '<br>';} ?>
<meta property="og:image" content="<?php echo $image; ?>" />
<meta property="og:image:url" content="<?php echo $image; ?>" />
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
<meta property="og:image:type" content="<?php echo $image_info['mime']; ?>" />
<meta property="og:image:width" content="<?php echo $image_info[0]; ?>" />
<meta property="og:image:height" content="<?php echo $image_info[1]; ?>" />
<?php } ?>
<meta property="og:description" content="<?php echo $description; ?>" />
    <?php if  ( $facebook_type !== 'product') { ?>
<meta property="og:type" content="<?php echo PLUGIN_SDATA_FOG_TYPE_SITE; ?>" />
    <?php if (PLUGIN_SDATA_STREET_ADDRESS !== '') { ?>
<meta property="business:contact_data:street_address" content="<?php echo PLUGIN_SDATA_STREET_ADDRESS; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_LOCALITY !== '') { ?>
<meta property="business:contact_data:locality" content="<?php echo PLUGIN_SDATA_LOCALITY; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_REGION !== '') { ?>
<meta property="business:contact_data:region" content="<?php echo PLUGIN_SDATA_REGION; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_POSTALCODE !== '') { ?>
<meta property="business:contact_data:postal_code" content="<?php echo PLUGIN_SDATA_POSTALCODE; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_COUNTRYNAME !== '') { ?>
<meta property="business:contact_data:country_name" content="<?php echo PLUGIN_SDATA_COUNTRYNAME; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_EMAIL !== '') { ?>
<meta property="business:contact_data:email" content="<?php echo PLUGIN_SDATA_EMAIL; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_TELEPHONE !== '') { ?>
<meta property="business:contact_data:phone_number" content="<?php echo PLUGIN_SDATA_TELEPHONE; ?>" />
    <?php } ?>
    <?php if (PLUGIN_SDATA_FAX !== '') { ?>
<meta property="business:contact_data:fax_number" content="<?php echo PLUGIN_SDATA_FAX; ?>" />
    <?php } ?>
<meta property="business:contact_data:website" content="<?php echo HTTP_SERVER; ?>" />
<!-- eof Facebook structured data general-->
<?php } else { ?>
<!-- Facebook structured data for product-->
<meta property="og:type" content="<?php echo trim(PLUGIN_SDATA_FOG_TYPE_PRODUCT); ?>" />
<meta property="product:availability" content="<?php if ($product_base_stock > 0) { ?>instock<?php } ?><?php if ($product_base_stock < 1) { ?>pending<?php } ?>" />
<meta property="product:brand" content="<?php echo $manufacturer_name; ?>" />
<meta property="product:category" content="<?php echo htmlentities($category_name); ?>" />
<meta property="product:condition" content="<?php echo PLUGIN_SDATA_FOG_PRODUCT_CONDITION; ?>" />
<?php if ($product_base_mpn !== '') {
                echo '<meta property="product:mfr_part_no" content="' . $product_base_mpn . '" />' . "\n";
            } ?>
<meta property="product:price:amount" content="<?php echo $product_base_displayed_price; ?>" />
<meta property="product:price:currency" content="<?php echo PLUGIN_SDATA_PRICE_CURRENCY; ?>" />
<meta property="product:product_link" content="<?php echo $url; ?>" />
<meta property="product:retailer" content="<?php echo PLUGIN_SDATA_FOG_APPID; ?>" />
<meta property="product:retailer_category" content="<?php echo htmlentities($category_name); ?>" />
<meta property="product:retailer_part_no" content="<?php echo $product_base_sku; ?>" />
<!-- eof Facebook structured data -->
<?php } }//end facebook enabled  ?>
<?php if (PLUGIN_SDATA_TWITTER_CARD_ENABLE === 'true') { ?>
<!-- Twitter Card markup -->
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="<?php echo PLUGIN_SDATA_TWITTER_USERNAME; ?>" />
<meta name="twitter:title" content="<?php echo $title; ?>" />
<meta name="twitter:description" content="<?php echo $description; ?>" />
<?php $image = ($image_default ? $image_default_twitter : $image); ?>
<meta name="twitter:image" content="<?php echo $image; ?>" />
<meta name="twitter:image:alt" content="<?php echo htmlentities($image_alt, ENT_QUOTES, CHARSET, false); ?>" />
<meta name="twitter:url" content="<?php echo htmlentities($url, ENT_COMPAT, CHARSET, false); ?>" />
<meta name="twitter:domain" content="<?php echo HTTP_SERVER; ?>" />
<!-- eof Twitter Card markup -->
<?php } //end of Twitter enabled ?>
<?php //google+ markup
if (PLUGIN_SDATA_GOOGLE_PUBLISHER !== '') { ?>
<!-- Google+-->
<link href="<?php echo PLUGIN_SDATA_GOOGLE_PUBLISHER; ?>" rel="publisher" />
<!-- eof Google+--><?php } //eof Google+ ?>
