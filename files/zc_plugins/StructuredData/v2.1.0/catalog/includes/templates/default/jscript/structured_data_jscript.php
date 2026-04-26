<?php
// DO NOT LET YOUR IDE RE-FORMAT THE CODE: it is structured so the HTML SOURCE is readable/the parentheses line up.
declare(strict_types=1);

/**
 * This file MUST be loaded in HTML <head> since it generates meta tags.
 *
 * @author: torvista
 * @link: https://github.com/torvista/Zen_Cart-Structured_Data
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version structured_data_jscript.php torvista 26 Apr 2026
 */

/** directives for phpStorm code inspector
 * @var breadcrumb $breadcrumb
 * @var $canonicalLink
 * @var $current_page
 * @var $current_page_base
 * @var Product $product_info
 * @var Language $lng
 * @var queryFactory $db
 * @var $product_id
 * @var sniffer $sniffer
 */
// @formatter:off
if (!defined('PLUGIN_SDATA_ENABLE') || PLUGIN_SDATA_ENABLE !== 'true') {
    return;
}
// Set to true (boolean) to display debugging info.
// Changes from the gods are imposed irregularly, so I've left some ugly debug output available.
// Some is visible in the browser, the rest in the head (view with the browser Developer Tools).
// Most have the line number prefixed so you can search in Developer tools for the line number.
// Note that to display the content of a variable/array in a readable (formatted) form, you may use this inbuilt function: sd_printvar($variableName);
$debug_sd = false;

// defaults
$image_default = false; //if true, use a substitute generic image if no specific image found/exists
$facebook_type = 'business.business';

// Schema arrays
// ItemAvailability options
$itemAvailability = [
    'BackOrder' => 'https://schema.org/BackOrder',                     // The item is on back order. BackOrder needs a date for when it will become available
    'Discontinued' => 'https://schema.org/Discontinued',              // The item has been discontinued.
    'InStock' => 'https://schema.org/InStock',                         // The item is in stock.
    'InStoreOnly' => 'https://schema.org/InStoreOnly',                 // The item is only available for purchase in store.
    'LimitedAvailability' => 'https://schema.org/LimitedAvailability', // The item has limited availability.
    'OnlineOnly' => 'https://schema.org/OnlineOnly',                   // The item is available online only.
    'OutOfStock' => 'https://schema.org/OutOfStock',                   // The item is currently out of stock.
    'PreOrder' => 'https://schema.org/PreOrder',                       // The item is available for pre-order: buying in advance of a NEW product being released for sale. PreOrder needs a date for when a product will be released
    'PreSale' => 'https://schema.org/PreSale',                         // The item is available for ordering and delivery NOW before it is released for general availability.
    'SoldOut' => 'https://schema.org/SoldOut',                          // The item has been sold out.
];
$facebookAvailability = [
    'BackOrder' => 'pending',
    'Discontinued' => 'discontinued',
    'InStock' => 'instock',
    'InStoreOnly' => 'instock',
    'LimitedAvailability' => 'instock',
    'OnlineOnly' => 'instock',
    'OutOfStock' => 'oos',
    'PreOrder' => 'pending',
    'PreSale' => 'pending',
    'SoldOut' => 'oos',
];

// Product Condition options
$itemCondition = [
    'new' => 'NewCondition',
    'used' => 'UsedCondition',
    'refurbished' => 'RefurbishedCondition',
];

// Merchant Return Policy options
$returnPolicyCategory = [
    'Finite' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
    'NotPermitted' => 'https://schema.org/MerchantReturnNotPermitted',
    'Unlimited' => 'https://schema.org/MerchantReturnUnlimitedWindow',
    //'none' => 'https://schema.org/MerchantReturnUnspecified' // this Schema option is not supported by Google
];
// used only with 'finite' and 'unlimited'
$returnMethod = [
    'Kiosk' => 'https://schema.org/ReturnAtKiosk',
    'Mail' => 'https://schema.org/ReturnByMail',
    'Store ' => 'https://schema.org/ReturnInStore'
];
// eof Schema arrays

/**
 * json_encode returns a string enclosed by double quotes.
 * This function removes the quotes from around the string, so quotes can be used
 * around the php echo instead: otherwise the IDE identifies them as errors.
 * @param  string  $string
 * @return string
 */
function json_encode_sdata(string $string = ''): string
{
    return substr(json_encode($string), 1, -1);
}

/** parse string to make it suitable for embedding in the head
 * @param $string
 * @return string
 */
function sdata_prepare_string($string): string
{
    $string = html_entity_decode(trim($string), ENT_COMPAT, CHARSET);//convert HTML entities to characters
    $string = str_replace('</p>', '</p> ', $string); // add a space to separate text when tags are removed
    $string = str_replace('<br>', '<br> ', $string); // add a space to separate text when tags are removed
    $string = strip_tags($string);//remove html tags
    $string = str_replace(["\r\n", "\n", "\r"], '', $string); // remove LF, CR
    return preg_replace('/\s+/', ' ', $string); // remove multiple spaces
}

/** truncate long descriptions as legibly as possible
 * @param $string
 * @param $max_length
 * @return string
 */
function sdata_truncate($string, $max_length): string
{
    $string_json = json_encode_sdata($string);
    $string_json_length = strlen($string_json);
    //encoded multibyte characters increase the length
    if ($string_json_length > $max_length + 2) {//allow for enclosing double quotes
        //remove the enclosing double quotes
        $string_json_truncated = trim($string_json, '"');
        //truncate to $max_length, allowing for space to add ellipsis
        $string_json_truncated = substr($string_json_truncated, 0, $max_length - 3);
        //find last backslash from JSON encoding
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

/**
 *  Function to remove empty array values from schema array
 *
 * @param $value
 * @return array|mixed
 */
function sdata_clean_schema($value):mixed {
    if (is_array($value)) {
        $value = array_map('sdata_clean_schema', $value);
        $value = array_filter($value, fn($v) => $v !== '' && $v !== [] && $v !== null);
    }
    return $value;
}

/**
 *  Function to populate listItem schema array
 *
 * @param int $pos  position in list item array
 * @param string $link  Url of item
 * @param string $name  name of item
 * @param string $image Url of item image
 * @return array
 */
function sdata_set_listItem(int $pos, string $link, string $name, string $image): array {
    return [
        '@type' => 'ListItem',
        'position' => $pos,
        'url' => htmlspecialchars_decode($link),
        'name' => sdata_prepare_string($name),
        'image' => $image
    ];
}

// Initialize defaults to prevent php notices
$category_name = '';
$description = '';
$image = '';
$image_alt = '';
$language = $lng->language;
$manufacturer_name = '';
$product_base_displayed_price = '';
$product_base_mpn = '';
$product_base_sku = '';
$product_base_stock = 0;
$product_id = 0;
// If reviews have been modified to display on the product page, $reviewsArray may have already been created, so use it.
$reviewsArr = empty($reviewsArray) ? [] : $reviewsArray;
$title = '';

$url = $canonicalLink;
global $currencies;
$decimal_places = $currencies->currencies[PLUGIN_SDATA_PRICE_CURRENCY]['decimal_places'];

// Images
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
// Is a product page?
$is_product_page = (
    str_ends_with($current_page_base, '_info')
    && !empty($_GET['products_id'])
    && zen_get_info_page($_GET['products_id']) === $current_page_base
    && (isset($product_info) && is_object($product_info))
);

// Handle product page only
if ($is_product_page) {
    $product_id = (int)$product_info->fields['products_id'];
    $product_name = sdata_prepare_string($product_info->fields['lang'][$language['code']]['products_name'] ?? $product_info->fields['products_name']);

    // Use a function instead of $product_info so the notifier in function zen_get_products_description gets parsed
    // TODO has description already been parsed/modified anyway?
    $description = zen_get_products_description($product_id);
    $description = sdata_prepare_string($description);
    $title = htmlspecialchars(STORE_NAME . ' - ' . $product_name, ENT_QUOTES);
    $weight = (float)($product_info->fields['products_weight'] === '0' ? PLUGIN_SDATA_DEFAULT_WEIGHT : $product_info->fields['products_weight']);
    $tax_class_id = (int)$product_info->fields['products_tax_class_id'];
    if ($product_info->fields['product_is_call'] === '1') {
        $product_base_displayed_price = 0;
    } else {
        // Use price with tax, decimal point (not comma) to two decimal places
        $product_base_displayed_price = round(
            zen_get_products_actual_price($product_id) * (1 + zen_get_tax_rate($tax_class_id) / 100),
            2
        );
    }
    $product_date_added = $product_info->fields['products_date_added']; // Should never be default '0001-01-01 00:00:00'
    $manufacturer_name = zen_get_products_manufacturers_name($product_id);
    $product_base_stock = $product_info->fields['products_quantity'];

    // OOS BackOrder/PreSales need to have a date field
    $oosItemAvailability = array_key_exists(PLUGIN_SDATA_OOS_DEFAULT, $itemAvailability) ? $itemAvailability[PLUGIN_SDATA_OOS_DEFAULT] : $itemAvailability['OutOfStock'];
    if (PLUGIN_SDATA_OOS_DEFAULT === 'BackOrder' || PLUGIN_SDATA_OOS_DEFAULT === 'PreSales') {
        $backPreOrderDate = date('Y-m-d', strtotime('+' . (int)PLUGIN_SDATA_OOS_AVAILABILITY_DELAY . ' days'));
    } else {
        $backPreOrderDate = '';
    }

    // SKU: the merchant/shop-specific product identifier (normally different to the manufacturer mpn and the product gtin)
    $product_base_sku = $product_info->fields['products_model'];

    // MPN: Manufacturers Product Number
    $product_base_mpn = $product_info->fields['products_mpn'] ?? '';

    // Google Product Category
    // A field for Google Product Category needs to be added to the product table unless all products are under the same category.
    // Initialize with the default category
    $product_base_gpc = (int)PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY;

    // GTIN: a standardized international code UPC / GTIN-12 / EAN / JAN / ISBN / ITF-14. It may be subsequently updated by attribute data.
    // A field for GTIN needs to be added to the product table.
    $product_base_gtin = '';

    //productID (Schema only): same as GTIN
    $product_base_productID = $product_base_gtin;

    // Get base (non-attribute) GPC and GTIN from custom fields
    if ($sniffer->field_exists(TABLE_PRODUCTS, PLUGIN_SDATA_GPC_FIELD)) {
        $sql = 'SELECT ' . PLUGIN_SDATA_GPC_FIELD . ' FROM ' . TABLE_PRODUCTS . ' WHERE products_id = ' . $product_id;
        $result = $db->Execute($sql);
        $product_base_gpc = !empty($result->fields[PLUGIN_SDATA_GPC_FIELD]) ? $result->fields[PLUGIN_SDATA_GPC_FIELD] : (int)PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY;
    }
    if ($sniffer->field_exists(TABLE_PRODUCTS, PLUGIN_SDATA_GTIN_FIELD)) {
        $sql = 'SELECT ' . PLUGIN_SDATA_GTIN_FIELD . ' FROM ' . TABLE_PRODUCTS . ' WHERE products_id = ' . $product_id;
        $result = $db->Execute($sql);
        //Google Merchant Center feed complains if no GTIN. I use "no" in this field to omit that product from that feed.
        $product_base_gtin = (empty($result->fields[PLUGIN_SDATA_GTIN_FIELD]) || $result->fields[PLUGIN_SDATA_GTIN_FIELD] === 'no')
            ? ''
            : $result->fields[PLUGIN_SDATA_GTIN_FIELD];
    }

    //ATTRIBUTES
    //sku/mpn/gtin, price, stock may all vary per attribute
    $product_attributes = false;
    if ($product_info->fields['product_is_call'] === '0' && zen_has_product_attributes($product_id)) {
        $attribute_stock_handler = 'not_defined';
        $attribute_lowPrice = 0;
        $attribute_highPrice = 0;
        $offerCount = 1; // but exactly what is this? The sum of the stocks of all the variants or just the quantity of variants that exist? Should not be zero.
        $product_attributes = [];
        $attribute_prices = [];

// Get attribute info
        $sql = 'SELECT patrib.products_attributes_id, patrib.options_id, patrib.options_values_id, patrib.options_values_price, patrib.products_attributes_weight, patrib.products_attributes_weight_prefix, popt.products_options_name, poptv.products_options_values_name
                    FROM ' . TABLE_PRODUCTS_OPTIONS . ' popt
                    LEFT JOIN ' . TABLE_PRODUCTS_ATTRIBUTES . ' patrib ON (popt.products_options_id = patrib.options_id)
                    LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_VALUES . ' poptv ON (poptv.products_options_values_id = patrib.options_values_id AND poptv.language_id = popt.language_id)
                    WHERE patrib.products_id = ' . $product_id . '
                    AND popt.language_id = ' . $language['id'] . '
                    ORDER BY popt.products_options_name, poptv.products_options_values_name';
        $results = $db->Execute($sql);

        foreach ($results as $attribute) {
            if (zen_get_attributes_valid($product_id, $attribute['options_id'], $attribute['options_values_id'])) { // skips "display only"
                $product_attributes[$attribute['products_attributes_id']]['option_name_id'] = $attribute['options_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_name'] = $attribute['products_options_name'];
                $product_attributes[$attribute['products_attributes_id']]['option_value_id'] = $attribute['options_values_id'];
                $product_attributes[$attribute['products_attributes_id']]['option_value'] = $attribute['products_options_values_name'];
                $product_attributes[$attribute['products_attributes_id']]['price'] = zen_get_products_price_is_priced_by_attributes($product_id) ? $attribute['options_values_price']
                    : $product_base_displayed_price;
                // It's unlikely that a product price is 0, so only store non-zero prices to subsequently get the high and low prices
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
        /* $product_attributes array structure
        key is products_attributes_id, ordered by the option value text

        example
        [2682] => Array
            (
                [option_name_id] => 24
                [option_name] => SH cable
                [option_value_id] => 148
                [option_value] => SH-A01
                [price] => 26
            )
        **********************************************************************************************************
        ATTRIBUTE STOCK-MPN-GTIN HANDLING
        In the case where the attributes are products in their own right, the above array "$product_attributes" needs extra elements to be added PER ATTRIBUTE as per the example.
        [2682] => Array
            (
                [option_name_id] => 24
                [option_name] => SH cable
                [option_value_id] => 148
                [option_value] => SH-A01
                [price] => 26
                [stock] => 99
                [sku] => HT-1212
                [mpn] => SH-A01
                [gtin] => 5055780349776
            )
        The ZC core plugin POSM supplies stock and sku elements.
        The mpn and gtin are custom fields added into the product_options_stock table and defined in Admin.
        */
        switch (true) {

            // ZC core plugin POSM is in use
            case (defined('POSM_ENABLE') && POSM_ENABLE === 'true' && function_exists('is_pos_product') && is_pos_product($product_id)):
                $attribute_stock_handler = 'posm';
                if ($debug_sd) {
                    echo __LINE__ . ' Attributes: using POSM<br>';
                }

                //@todo bof hack to break to default attribute handling (no POSM/stock control) when dependant attributes are in use
                $option_ids = [];
                foreach ($product_attributes as $key => $product_attribute) {
                    $option_ids[] = $product_attribute['option_name_id'];
                }
                $option_id_min = min($option_ids);
                $option_id_max = max($option_ids);
                if ($option_id_min !== $option_id_max) {//there are two or more option names...RUN AWAY!
                    $attribute_stock_handler = 'posm_multiple'; //todo
                    break;
                }
                //eof hack

                $total_attributes_stock = 0;
                foreach ($product_attributes as $key => $product_attribute) {
                    //set some defaults from the base product in case there is no POSM entry for the attribute, despite an attribute existing
                    $product_attributes[$key]['stock'] = 0;
                    $product_attributes[$key]['sku'] = $product_base_productID;
                    $product_attributes[$key]['mpn'] = $product_base_mpn;
                    $product_attributes[$key]['gtin'] = $product_base_gtin;

                    //copied from observer function getOptionsStockRecord as it's a Protected function
                    $hash = generate_pos_option_hash($product_id, [$product_attribute['option_name_id'] => $product_attribute['option_value_id']]);

                    $posm_record = $db->Execute('SELECT * FROM ' . TABLE_PRODUCTS_OPTIONS_STOCK . ' WHERE products_id = ' . $product_id . ' AND pos_hash = "' . $hash . '" LIMIT 1', false, false, 0, true);
                    /* example output if extra fields have been added:
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
                    if ($sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_mpn') && $sniffer->field_exists(TABLE_PRODUCTS_OPTIONS_STOCK, 'pos_gtin')) {
                        //$product_attributes[$key]['mpn'] = $product_attributes[$key]['option_value'];//as per individual shop
                        $product_attributes[$key]['mpn'] = $posm_record->fields['pos_mpn'];//as per individual shop
                        $product_attributes[$key]['gtin'] = $posm_record->fields['pos_gtin'];//as per individual shop
                    }
                    //eof CUSTOM CODING REQUIRED***********************************
                }
                $offerCount = (max($product_base_stock + $total_attributes_stock, 1)); //maybe, hard to find a definition

                break;

            case (defined('STOCK BY ATTRIBUTES')):
                // over to YOU

            case (defined('NUMINIX PRODUCT VARIANTS INVENTORY MANAGER')):
                // over to YOU

            default://Zen Cart default/no handling of attribute stock...so no individual sku/mpn/gtin possible is per attribute
                $attribute_stock_handler = 'zc_default';
                foreach ($product_attributes as $key => $product_attribute) {
                    $product_attributes[$key]['stock'] = $product_base_stock;
                    $product_attributes[$key]['sku'] = $product_base_sku;//as per individual shop
                    $product_attributes[$key]['mpn'] = '';
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
// Handle a Category page (may have a product listing or sub-categories)
} else {
    if ($debug_sd) {
        echo __LINE__ . ': $current_page=' . $current_page . ', is NOT a product page<br>';
    }

    // bof create product/ sub-category listing schema
    global $current_category_has_products, $current_category_has_subcats, $current_category_id, $listing_sql;

    // There are no sub-categories: category contains products
    if ($current_category_has_products) {
        $listing_schema_name = 'Products';
        $listing_page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
        $listing_limit = (int)MAX_DISPLAY_PRODUCTS_LISTING;
        $listing_offset = ($listing_page - 1) * $listing_limit;
        $listing_query = $listing_sql . " LIMIT " . $listing_offset . ", " . $listing_limit;
        $listing_results = $db->Execute($listing_query);

        if ($listing_results->RecordCount() > 0) {
            $list_pos = 1;
            foreach ($listing_results as $item) {
                $sql_extra = "SELECT products_image, products_model
                              FROM " . TABLE_PRODUCTS . "
                              WHERE products_id = " . (int)$item['products_id'];
                $extra_info = $db->Execute($sql_extra);

                // use data from the lookup, fallback to empty string
                $prod_image = $extra_info->fields['products_image'] ?? '';
                $prod_model = $extra_info->fields['products_model'] ?? '';
                $item_link = zen_href_link(zen_get_info_page($item['products_id']), 'cPath=' . $_GET['cPath'] . '&products_id=' . $item['products_id']);
                $item_image_url = ($prod_image !== '')
                    ? HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $prod_image
                    : $image_default_facebook;
                $prod_name = $item['products_name'] ?? zen_get_products_name((int)$item['products_id']);
                $listing_schema[] = sdata_set_listItem($list_pos, $item_link, $prod_name, $item_image_url);
                $list_pos++;
            }
        }
        // Category contains sub-categories
    } elseif ($current_category_has_subcats) {
        $listing_schema_name = 'Subcategories';
        $sub_cat_sql = "SELECT c.categories_id, cd.categories_name, c.categories_image
                            FROM " . TABLE_CATEGORIES . " c
                            LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON (c.categories_id = cd.categories_id AND cd.language_id = " . (int)$_SESSION['languages_id'] . ")
                            WHERE c.parent_id = " . (int)$current_category_id . " AND c.categories_status = 1
                            ORDER BY c.sort_order ASC, cd.categories_name ASC";
        $sub_cat_data = $db->Execute($sub_cat_sql);

        $base_cPath = !empty($_GET['cPath']) ? $_GET['cPath'] . '_' : '';

        $list_pos = 1;
        foreach ($sub_cat_data as $list_category) {
            $list_cpath = $base_cPath . $list_category['categories_id'];
            $item_link = zen_href_link(FILENAME_DEFAULT, 'cPath=' . $list_cpath);
            $item_image_url = (!empty($list_category['categories_image']))
                ? HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $list_category['categories_image']
                : '';
            $listing_schema[] = sdata_set_listItem($list_pos, $item_link, $list_category['categories_name'], $item_image_url);
            $list_pos++;
        }
    }
    // eof create product/sub-category listing schema

    if (isset($_GET['cPath'])) {
        $category_name = zen_get_category_name($current_category_id);

        // a valid category
        if (!empty($category_name)) {
            $category_image = zen_get_categories_image($current_category_id);

            if (empty($category_image)) {
                $image_default = true;
            } else {
                $image = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $category_image;
            }
            if ($debug_sd) {
                echo __LINE__ . ' $category_image=' . $category_image . '<br>';
                echo __LINE__ . ' gettype $category_image=' . gettype($category_image) . '<br>';
            }

            $description = zen_get_category_description($current_category_id) !== '' ? zen_get_category_description($current_category_id) : META_TAG_DESCRIPTION;
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

    // Some other page - not a product or category page
    } else {
        if ($debug_sd) {
            echo __LINE__ . ' is "Other" page<br>';
        }

        $image_default = true;
        //$image_alt = $breadcrumb_this_page;//todo, needed??
        $title = META_TAG_TITLE;
        $description = META_TAG_DESCRIPTION;
    }
}

// $description could be null from META_TAG_DESCRIPTION
if (empty($description)) {
    $description = '';
}
$description = sdata_prepare_string($description);
// Build sameAs array
// ZenExpert - note: Contact Us page should NOT be in sameAs list if it's not an external link
$sameAs = [];

// Add the comma-separated list from PLUGIN_SDATA_SAMEAS
if (PLUGIN_SDATA_SAMEAS !== '') {
    $sameAs = array_map(
        static fn($url) => trim($url, " \t\n\r\0\x0B\"'"),
        explode(',', PLUGIN_SDATA_SAMEAS)
    );
}

// Add individual social URLs
foreach ([PLUGIN_SDATA_FOG_PAGE, PLUGIN_SDATA_TWITTER_PAGE, PLUGIN_SDATA_GOOGLE_PUBLISHER] as $url) {
    if (!empty($url)) {
        $sameAs[] = trim($url, " \t\n\r\0\x0B\"'");
    }
}

// Remove duplicates + empty values
$sameAs = array_values(array_filter(array_unique($sameAs)));

// Build acceptedPaymentMethod list
$PaymentMethods = [];

$PaymentMethod_array = explode(', ', PLUGIN_SDATA_ACCEPTED_PAYMENT_METHODS);
foreach ($PaymentMethod_array as $payment_method) {
    $PaymentMethods[] = "https://purl.org/goodrelations/v1#" . trim($payment_method);
}

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

// build a Reviews array if not already created by the product_info page
if ($is_product_page) {
    if (count($reviewsArr) === 0) {
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
                $reviewsArr[] = [
                    'id' => $review['reviews_id'],
                    'customersName' => $review['customers_name'],
                    'reviewsRating' => $review['reviews_rating'],
                    'dateAdded' => (!empty($review['date_added']) ? $review['date_added'] : PLUGIN_SDATA_REVIEW_DEFAULT_DATE), // $review['date_added'] may be NULL
                    'reviewsText' => $review['reviews_text']
                ];
                $ratingSum += $review['reviews_rating']; // mc12345678 2022-07-04: If going to omit this review now or in the future, then need to consider this value.
            }
        }
    }
// if no reviews, make a default review to satisfy Rich Results testing tool
    if (count($reviewsArr) === 0 && PLUGIN_SDATA_REVIEW_USE_DEFAULT === 'true') {
        $reviewsArr[0] = [
            'id' => 0, // not used
            'customersName' => 'anonymous',
            'reviewsRating' => (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE,
            'dateAdded' => $product_date_added,
            'reviewsText' => ''
        ];
        $ratingSum = (int)PLUGIN_SDATA_REVIEW_DEFAULT_VALUE;
    }

    $reviewCount = count($reviewsArr);
    $ratingValue = round($ratingSum / $reviewCount, 1);
}

//Merchant Return Policy
//common code block used in attribute-handling option and simple product
$hasMerchantReturnPolicy = [];

if (!empty(PLUGIN_SDATA_RETURNS_POLICY_COUNTRY)) {
    $policyData = [
        '@type' => 'MerchantReturnPolicy',
        'applicableCountry' => PLUGIN_SDATA_RETURNS_APPLICABLE_COUNTRY,
        'returnPolicyCategory' => $returnPolicyCategory[PLUGIN_SDATA_RETURNS_POLICY],
        'returnMethod' => $returnMethod[PLUGIN_SDATA_RETURNS_METHOD]
    ];

    if (PLUGIN_SDATA_RETURNS_POLICY === 'Finite') {
        $policyData['merchantReturnDays'] = (int)PLUGIN_SDATA_RETURNS_DAYS;
    }

    $rType = defined('PLUGIN_SDATA_RETURNS_TYPE') ? PLUGIN_SDATA_RETURNS_TYPE : 'FreeReturn';
    $rFeeVal = defined('PLUGIN_SDATA_RETURNS_FEE') ? PLUGIN_SDATA_RETURNS_FEE : '0';
    $rCurrency = defined('PLUGIN_SDATA_PRICE_CURRENCY') ? PLUGIN_SDATA_PRICE_CURRENCY : 'GBP';

    // Set the Schema URL for the fee type
    if ($rType === 'RestockingFees') {
        // although valid enumeration, Google doesn't accept it so we must use ReturnFeesCustomerResponsibility
        $policyData['returnFees'] = 'https://schema.org/ReturnFeesCustomerResponsibility';
    } else {
        $policyData['returnFees'] = 'https://schema.org/' . $rType;
    }

    // Handle "RestockingFees" (Percentage or Fixed)
    if ($rType === 'RestockingFees') {
        // Check for percentage
        if (str_contains($rFeeVal, '%')) {
            // It is a percentage (e.g. "20%")
            $policyData['description'] = "A restocking fee of $rFeeVal applies to returned items.";

            // Calculate actual value if we have a product price
            if (isset($product_base_displayed_price) && is_numeric($product_base_displayed_price)) {
                $percent = (float)str_replace('%', '', $rFeeVal) / 100;

                $policyData['restockingFee'] = [
                    '@type' => 'MonetaryAmount',
                    'currency' => $rCurrency,
                    'value' => number_format($product_base_displayed_price * $percent, $decimal_places, '.', '')
                ];
            }
        } else {
            // It is a fixed amount (e.g. "10.00")
            $policyData['description'] = "A restocking fee of $rCurrency $rFeeVal applies.";
            $policyData['restockingFee'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $rCurrency,
                'value' => number_format((float)$rFeeVal, $decimal_places, '.', '')
            ];
        }

        // Handle "ReturnShippingFees" (Fixed shipping cost)
    } elseif ($rType === 'ReturnShippingFees' && (float)$rFeeVal > 0) {
        $policyData['returnShippingFeesAmount'] = [
            '@type' => 'MonetaryAmount',
            'currency' => $rCurrency,
            'value' => number_format((float)$rFeeVal, $decimal_places, '.', '')
        ];
    }

    $hasMerchantReturnPolicy = [
        'hasMerchantReturnPolicy' => $policyData
    ];
}
?>
<?php
if (PLUGIN_SDATA_SCHEMA_ENABLE === 'true') {
    /*
     * Organisation Schema
     */

    $organization_type = 'Organization';

    if (defined('PLUGIN_SDATA_ORGANIZATION_TYPE') && PLUGIN_SDATA_ORGANIZATION_TYPE !== '') {

        // LocalBusiness or subtype
        if (PLUGIN_SDATA_ORGANIZATION_TYPE === 'LocalBusiness') {
            $organization_type =
                (defined('PLUGIN_SDATA_LOCAL_BUSINESS_TYPE') && PLUGIN_SDATA_LOCAL_BUSINESS_TYPE !== '')
                    ? PLUGIN_SDATA_LOCAL_BUSINESS_TYPE
                    : 'LocalBusiness';
        // Any other valid type
        } else {
            $organization_type = PLUGIN_SDATA_ORGANIZATION_TYPE;
        }
    }

    /*
     * Build base schema
     */
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $organization_type,

        // Core fields
        'name' => PLUGIN_SDATA_LOCAL_BUSINESS_NAME ?: PLUGIN_SDATA_LEGAL_NAME ?: STORE_NAME,
        'legalName' => PLUGIN_SDATA_LEGAL_NAME,
        'description' => sdata_prepare_string(PLUGIN_SDATA_DESCRIPTION),
        'url' => HTTP_SERVER,
        'logo' => PLUGIN_SDATA_LOGO,
        'email' => PLUGIN_SDATA_EMAIL,
        'telephone' => PLUGIN_SDATA_TELEPHONE,
        'faxNumber' => PLUGIN_SDATA_FAX,

        // Identifiers
        'duns' => PLUGIN_SDATA_DUNS,
        'taxID' => PLUGIN_SDATA_TAXID,
        'vatID' => PLUGIN_SDATA_VATID,

        // Address
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => PLUGIN_SDATA_STREET_ADDRESS,
            'addressLocality' => PLUGIN_SDATA_LOCALITY,
            'addressRegion' => PLUGIN_SDATA_REGION,
            'postalCode' => PLUGIN_SDATA_POSTALCODE,
            'addressCountry' => PLUGIN_SDATA_COUNTRYNAME,
        ],

        // Contact point
        'contactPoint' => [
            [
                '@type' => 'ContactPoint',
                'telephone' => PLUGIN_SDATA_TELEPHONE,
                'contactType' => 'customer service',
            ]
        ],

        // Optional arrays
        'sameAs' => $sameAs,
        'areaServed' => (PLUGIN_SDATA_AREA_SERVED !== '' ? array_map('trim', explode(',', PLUGIN_SDATA_AREA_SERVED)) : []),
        'availableLanguage' => (PLUGIN_SDATA_AVAILABLE_LANGUAGE !== '' ? array_map('trim', explode(',', PLUGIN_SDATA_AVAILABLE_LANGUAGE)) : []),
    ];

    /*
     * LocalBusiness extras (NOT for OnlineBusiness or Organization)
     */
    if ($organization_type !== 'Organization' && $organization_type !== 'OnlineBusiness') {

        // Images
        if (PLUGIN_SDATA_PROPERTY_IMAGE !== '') {
            $photo = trim(PLUGIN_SDATA_PROPERTY_IMAGE);
            $schema['image'] = str_contains($photo, ',')
                ? array_map('trim', explode(',', $photo))
                : $photo;
        }

        // Price range
        if (PLUGIN_SDATA_PRICE_RANGE !== '') {
            $schema['priceRange'] = PLUGIN_SDATA_PRICE_RANGE;
        }

        /*
         * Opening hours (LocalBusiness only)
         */
        if (defined('PLUGIN_SDATA_HOURS') && PLUGIN_SDATA_HOURS !== '') {
            $day_map = [
                'Mon' => 'https://schema.org/Monday',
                'Tue' => 'https://schema.org/Tuesday',
                'Wed' => 'https://schema.org/Wednesday',
                'Thu' => 'https://schema.org/Thursday',
                'Fri' => 'https://schema.org/Friday',
                'Sat' => 'https://schema.org/Saturday',
                'Sun' => 'https://schema.org/Sunday',
            ];

            $hours_specs = [];
            // break into groups (e.g., Weekdays | Weekends)
            $groups = explode('|', PLUGIN_SDATA_HOURS);

            foreach ($groups as $group) {
                // Separate days from times (Mon,Tue;09:00-17:00)
                $parts = explode(';', $group);
                if (count($parts) !== 2) {
                    continue; // skip malformed
                }

                $days_str = trim($parts[0]);
                $times_str = trim($parts[1]);

                // process days
                $schema_days = [];
                $raw_days = explode(',', $days_str);
                foreach ($raw_days as $d) {
                    $d = trim($d);
                    if (isset($day_map[$d])) {
                        $schema_days[] = $day_map[$d];
                    }
                }
                if (empty($schema_days)) {
                    continue;
                }

                // process times (handles split shifts like 09:00-12:00,13:00-17:00)
                $time_ranges = explode(',', $times_str);
                foreach ($time_ranges as $range) {
                    $times = explode('-', $range);
                    if (count($times) !== 2) {
                        continue;
                    }
                    $hours_specs[] = [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => $schema_days,
                        'opens' => trim($times[0]),
                        'closes' => trim($times[1])
                    ];
                }
            }

            if (!empty($hours_specs)) {
                $schema['contactPoint'][0]['hoursAvailable'] = $hours_specs;
            }
        }
    }

    $schema = sdata_clean_schema($schema);
    ?>
<script title="Structured Data: schemaOrganisation" type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
    /*
     *  Breadcrumb Schema
     */

    // Get breadcrumb trail
    $trail = $breadcrumb->getTrail();

    // Build normalized breadcrumb list
    $items = [];

    foreach ($trail as $i => $item) {
        $items[] = [
            'position' => $i + 1,
            'id' => htmlspecialchars_decode($item['link']),
            'name' => $item['title']
        ];
    }

    $breadcrumb_count = count($items);


    // Remove duplicate last breadcrumb (Bootstrap issue)
    if ($breadcrumb_count > 1 &&
        $items[$breadcrumb_count - 1]['id'] === $items[$breadcrumb_count - 2]['id']
    ) {
        array_pop($items);
        $breadcrumb_count--;
    }

    // Ensure final breadcrumb has a URL Zen cart default is not to show the url.
    if ($breadcrumb_count > 0 && empty($items[$breadcrumb_count - 1]['id'])) {
        $items[$breadcrumb_count - 1]['id'] = htmlspecialchars_decode($canonicalLink);
    }

    // Only output schema if more than one breadcrumb exists
    if ($breadcrumb_count > 1) {

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($items as $item) {
            $breadcrumbSchema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $item['position'],
                'name' => $item['name'],
                'item' => $item['id']
            ];
        }
?>
<script title="Structured Data: schemaBreadcrumb" type="application/ld+json">
<?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
    } // end breadcrumb

    // Check if we are on the Contact Us page
    if ($current_page_base === 'contact_us') {

        // Build ContactPoint
        $contactPoint = [
            '@type' => 'ContactPoint',
            'telephone' => sdata_prepare_string(STORE_TELEPHONE_CUSTSERVICE),
            'contactType' => 'customer service',
        ];

        if (!empty(PLUGIN_SDATA_AREA_SERVED)) {
            $contactPoint['areaServed'] = PLUGIN_SDATA_AREA_SERVED;
        }

        if (!empty(PLUGIN_SDATA_AVAILABLE_LANGUAGE)) {
            $contactPoint['availableLanguage'] = PLUGIN_SDATA_AVAILABLE_LANGUAGE;
        }

        // Build mainEntity Organization
        $mainEntity = [
            '@type' => 'Organization',
            'name' => sdata_prepare_string(STORE_NAME),
            'url' => HTTP_SERVER,
            'contactPoint' => $contactPoint
        ];

        // Build ContactPage schema
        $contactSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'name' => sdata_prepare_string(STORE_NAME) . ' Contact Us',
            'description' => 'Contact information for ' . sdata_prepare_string(STORE_NAME),
            'url' => htmlspecialchars_decode(zen_href_link(FILENAME_CONTACT_US, '', 'SSL')),
            'mainEntity' => $mainEntity
        ];

        $contactSchema = sdata_clean_schema($contactSchema);
?>
<script title="Structured Data: schemaContactPage" type="application/ld+json">
<?= json_encode($contactSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
    } // end contact us.

// bof Product Listing schema for category pages with products
    if (!empty($listing_schema)) {

        // Create ItemList schema header
        $itemListSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $listing_schema_name,
            'itemListElement' => []
        ];

        // Add items to ItemList
        foreach ($listing_schema as $element) {
            // Each $element is already a valid ListItem array
            $itemListSchema['itemListElement'][] = $element;
        }

        // Remove any empty schema entries
        $itemListSchema = sdata_clean_schema($itemListSchema);
?>
<script title="Structured Data: schemaItemList" type="application/ld+json">
<?= json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
    }
// eof Product Listing schema for category pages with products

// Create web page schema
    $webPageSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => sdata_truncate(META_TAG_TITLE, PLUGIN_SDATA_MAX_NAME),
        'url' => htmlspecialchars_decode($canonicalLink),
        'isPartOf' => [
            '@type' => "WebSite",
            'name' => PLUGIN_SDATA_LOCAL_BUSINESS_NAME ?: PLUGIN_SDATA_LEGAL_NAME ?: STORE_NAME,
            'url' => HTTP_SERVER
            ]
    ];

    // add description if home page
    if (!$is_product_page && $breadcrumb_count === 1) {
        $webSiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => PLUGIN_SDATA_LOCAL_BUSINESS_NAME ?: PLUGIN_SDATA_LEGAL_NAME ?: STORE_NAME,
            'url' => HTTP_SERVER,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => HTTP_SERVER . DIR_WS_CATALOG .'?main_page=search_result&search_in_description=1&keyword={search_term_string}',
                'query-input' => 'required name=search_term_string'
                ]
            ]
?>
<script title="Structured Data: schemaWebSite" type="application/ld+json">
<?= json_encode($webSiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
        $webPageSchema['description'] = META_TAG_DESCRIPTION;
    } elseif (!$is_product_page && $breadcrumb_count > 1 ) {
        // add the category description
        $webPageSchema['description'] = $description; // optional but recommended
    }

// eof Create web page schema

// Product schema for product pages
    if ($is_product_page) {

        // Base Product schema
        $productSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => sdata_truncate($product_name, PLUGIN_SDATA_MAX_NAME),
            'url' => htmlspecialchars_decode($canonicalLink),
            'image' => $image,
            'description' => sdata_truncate($description, PLUGIN_SDATA_MAX_DESCRIPTION),
            'sku' => $product_base_sku,
            'weight' => [
                '@type' => 'QuantitativeValue',
                'value' => $weight,
                'unitCode' => ((SHIPPING_WEIGHT_UNITS === 'kgs') ? 'KGM' : 'LBR'),
            ],
            'brand' => [
                '@type' => 'Brand',
                'name' => (isset($manufacturer_name) && trim($manufacturer_name) !== '')
                    ? $manufacturer_name
                    : (defined('STORE_NAME') ? STORE_NAME : ''),
            ],
            'category' => $category_name,
        ];

        // Optional identifiers
        if ($product_base_mpn !== '') {
            $productSchema['mpn'] = $product_base_mpn;
        }
        if ($product_base_gtin !== '') {
            $productSchema['gtin'] = $product_base_gtin;
        }
        if ($product_base_productID !== '') {
            $productSchema['productID'] = $product_base_productID;
        }

        // Google product category (both variants kept)
        if ($product_base_gpc !== 0) {
            $productSchema['googleProductCategory']  = (string)$product_base_gpc;
            $productSchema['google_product_category'] = (string)$product_base_gpc;
        }

        /*
         * OFFERS
         * Three modes:
         *  - POSM attribute stock handler → offers as array
         *  - Default attribute pricing → offers as Offer/AggregateOffer object
         *  - Simple product (no attributes) → offers as Offer object
         */

        if ($product_attributes) {

            switch ($attribute_stock_handler) {

                case 'posm':
                    // POSM: multiple offers, one per attribute
                    $productSchema['__comment'] = 'attribute stock handling:' . $attribute_stock_handler;

                    $offers = [];

                    $attributes_count = count($product_attributes);
                    foreach ($product_attributes as $index => $product_attribute) {

                        $offer = [
                            '@type' => 'Offer',
                            'price' => number_format((float)$product_attribute['price'], $decimal_places, '.', ''),
                            'weight' => [
                                '@type' => 'QuantitativeValue',
                                'value' => ($weight + $product_attribute['weight'] > 0
                                                ? $weight + $product_attribute['weight']
                                                : $weight),
                                'unitCode' => ((SHIPPING_WEIGHT_UNITS === 'kgs') ? 'KGM' : 'LBR'),
                            ],
                            'priceCurrency' => PLUGIN_SDATA_PRICE_CURRENCY,
                            'availability' => $product_attribute['stock'] > 0
                                ? $itemAvailability['InStock']
                                : $oosItemAvailability,
                            'priceValidUntil' => date('Y') . '-12-31',
                            'url' => htmlspecialchars_decode($canonicalLink),
                        ];

                        // Optional: merchant return policy (previously injected as raw JSON)
                        if (!empty($hasMerchantReturnPolicy)) {
                            // If $hasMerchantReturnPolicy is an array, merge it here.
                            // If it's a JSON fragment, you may want to refactor it to an array first.
                            $offer = array_merge($offer, $hasMerchantReturnPolicy);
                        }

                        if (!empty($product_attribute['sku'])) {
                            $offer['sku'] = $product_attribute['sku'];
                        }
                        if (!empty($product_attribute['mpn'])) {
                            $offer['mpn'] = $product_attribute['mpn'];
                        }
                        if (!empty($product_attribute['gtin'])) {
                            $offer['gtin'] = $product_attribute['gtin'];
                        }

                        if ($product_attribute['stock'] < 1 && $backPreOrderDate !== '') {
                            $offer['availability_date'] = $backPreOrderDate;
                        }

                        $offers[] = $offer;
                    }

                    $productSchema['offers'] = $offers;
                    break;

                default:
                    // Default Zen Cart attribute pricing
                    $productSchema['__comment'] = 'attribute stock handling default:' . $attribute_stock_handler;

                    $offer = [
                        'url' => htmlspecialchars_decode($canonicalLink),
                        'priceCurrency' => PLUGIN_SDATA_PRICE_CURRENCY,
                        'priceValidUntil'=> date('Y') . '-12-31',
                        'itemCondition' => 'https://schema.org/' . $itemCondition[PLUGIN_SDATA_FOG_PRODUCT_CONDITION],
                        'availability' => ($product_base_stock > 0 ? $itemAvailability['InStock'] : $oosItemAvailability),
                        'seller' => [
                            '@type' => 'Organization',
                            'name' => STORE_NAME,
                        ],
                    ];

                    // Optional: merchant return policy
                    if (!empty($hasMerchantReturnPolicy)) {
                        $offer = array_merge($offer, $hasMerchantReturnPolicy);
                    }

                    if ($attribute_lowPrice === $attribute_highPrice) {
                        $offer['@type'] = 'Offer';
                        $offer['price'] = $attribute_lowPrice;
                    } else {
                        $offer['@type']    = 'AggregateOffer';
                        $offer['lowPrice'] = $attribute_lowPrice;
                        $offer['highPrice'] = $attribute_highPrice;
                        $offer['offerCount'] = $offerCount;
                    }

                    if ($backPreOrderDate !== '') {
                        $offer['availability_date'] = $backPreOrderDate;
                    }

                    $leadTime = ($product_base_stock > 0
                        ? (int)PLUGIN_SDATA_DELIVERYLEADTIME
                        : (int)PLUGIN_SDATA_DELIVERYLEADTIME_OOS);
                    if (!empty($leadTime)) {
                        $offer['deliveryLeadTime'] = [
                            '@type' => 'QuantitativeValue',
                            'value' => $leadTime,
                            'unitCode' => 'DAY',
                        ];
                    }

                    if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') {
                        $offer['eligibleRegion'] = PLUGIN_SDATA_ELIGIBLE_REGION;
                    }

                    $offer['acceptedPaymentMethod'] =  $PaymentMethods;

                    $productSchema['offers'] = $offer;
                    break;
            }

        } else {
            // Simple product (no attributes)
            $offer = [
                '@type' => 'Offer',
                'price' => $product_base_displayed_price,
                'url' => htmlspecialchars_decode($canonicalLink),
                'priceCurrency' => PLUGIN_SDATA_PRICE_CURRENCY,
                'priceValidUntil'=> date('Y') . '-12-31',
                'itemCondition' => 'https://schema.org/' . $itemCondition[PLUGIN_SDATA_FOG_PRODUCT_CONDITION],
                'availability' => ($product_base_stock > 0 ? $itemAvailability['InStock'] : $oosItemAvailability),
                'seller' => [
                        '@type' => 'Organization',
                        'name' => STORE_NAME,
                    ],
            ];

            // Optional: merchant return policy
            if (!empty($hasMerchantReturnPolicy)) {
                $offer = array_merge($offer, $hasMerchantReturnPolicy);
            }

            if ($backPreOrderDate !== '') {
                $offer['availability_date'] = $backPreOrderDate;
            }

            $leadTime = ($product_base_stock > 0
                ? (int)PLUGIN_SDATA_DELIVERYLEADTIME
                : (int)PLUGIN_SDATA_DELIVERYLEADTIME_OOS);
            if (!empty($leadTime)) {
                $offer['deliveryLeadTime'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => $leadTime,
                    'unitCode' => 'DAY',
                ];
            }

            if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') {
                $offer['eligibleRegion'] = PLUGIN_SDATA_ELIGIBLE_REGION;
            }

            $offer['acceptedPaymentMethod']  = $PaymentMethods;

            $productSchema['offers'] = $offer;
        }

        /*
         * REVIEWS & AGGREGATE RATING
         */

        if ($reviewCount > 0) {
            $productSchema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $ratingValue,
                'reviewCount' => $reviewCount,
            ];

            $reviews = [];
            if (!empty($reviewsArr)) {
                foreach ($reviewsArr as $review) {
                    $reviews[] = [
                        '@type' => 'Review',
                        'author' => [
                            '@type' => 'Person',
                            'name' => strtok($review['customersName'], ' '),
                        ],
                        'reviewBody' => ($review['reviewsText'] ?: 'Reviewer did not leave a written comment.'),
                        'datePublished'=> substr($review['dateAdded'], 0, 10),
                        'reviewRating' => [
                            '@type' => 'Rating',
                            'ratingValue'=> $review['reviewsRating'],
                        ],
                    ];
                }
            }

            $productSchema['review'] = $reviews;
        }

        // Remove empty schema entries
        $productSchema = sdata_clean_schema($productSchema);
?>
<script title="Structured Data: schemaProduct" type="application/ld+json">
<?= json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
// add product name to webpage schema
$webPageSchema['about'] = [
    '@type' => 'Product',
    'name' => sdata_truncate($product_name, PLUGIN_SDATA_MAX_NAME)
   ];
}
// eof Product schema for product pages

// output web page schema
?>
<script title="Structured Data: schemaWebPage" type="application/ld+json">
<?= json_encode($webPageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL; ?>
</script>
<?php
}
// eof Schema enabled

if (PLUGIN_SDATA_FOG_ENABLE === 'true') {
?>
    <!-- Facebook structured data general-->
<?php
// opening php tags from this point forward must be at the beginning of the line or the meta tag formatting will look wrong.
    if (PLUGIN_SDATA_FOG_APPID !== '') {
?>
    <meta property="fb:app_id" content="<?= (int)PLUGIN_SDATA_FOG_APPID ?>">
<?php
    }
?>
<?php
    if (PLUGIN_SDATA_FOG_ADMINID !== '') {
?>
    <meta property="fb:admins" content="<?= (int)PLUGIN_SDATA_FOG_ADMINID ?>">
<?php
    }
?>
    <meta property="og:title" content="<?= $title ?>">
    <meta property="og:site_name" content="<?= STORE_NAME ?>">
    <meta property="og:url" content="<?= $canonicalLink ?>">
<?php
    if (!empty($locale)) {
?>
    <meta property="og:locale" content="<?= $locale ?>">
<?php
        if (count($locales_array) > 0) {
            foreach($locales_array as $key=>$value){
?>
    <meta property="og:locale:alternate" content="<?= $value ?>">
<?php
            }
        }
    }
    $image = ($image_default ? $image_default_facebook : $image);
    if ($debug_sd) {
        echo __LINE__ . ' $image_default=' . $image_default . '<br>';
    }
?>
    <meta property="og:image" content="<?= $image ?>">
    <meta property="og:image:url" content="<?= $image ?>">
<?php
    if (is_readable(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image))) {
        $image_info = @getimagesize(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image));
//log the problem for correction
        if ($image_info === false) {
            error_log(__FILE__ . ":getimagesize($image) returned FALSE: image is corrupt");
            $image = DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE;
            $image_info = getimagesize(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image));
?>
            <!-- ERROR: image is corrupt: see debug logs -->
<?php
        }
?>
    <meta property="og:image:alt" content="<?= htmlentities($image_alt, ENT_QUOTES, CHARSET, false) ?>">
    <meta property="og:image:type" content="<?= $image_info['mime'] ?>">
    <meta property="og:image:width" content="<?= $image_info[0] ?>">
    <meta property="og:image:height" content="<?= $image_info[1] ?>">
<?php
    }
?>
    <meta property="og:description" content="<?= htmlentities($description) ?>">
<?php
    if ($facebook_type !== 'product') {
?>
    <meta property="og:type" content="<?= PLUGIN_SDATA_FOG_TYPE_SITE ?>">
<?php
        if (PLUGIN_SDATA_STREET_ADDRESS !== '') {
?>
    <meta property="business:contact_data:street_address" content="<?= PLUGIN_SDATA_STREET_ADDRESS ?>">
<?php
        }
        if (PLUGIN_SDATA_LOCALITY !== '') {
?>
    <meta property="business:contact_data:locality" content="<?= PLUGIN_SDATA_LOCALITY ?>">
<?php
        }
        if (PLUGIN_SDATA_REGION !== '') {
 ?>
    <meta property="business:contact_data:region" content="<?= PLUGIN_SDATA_REGION ?>">
<?php
        }
        if (PLUGIN_SDATA_POSTALCODE !== '') {
?>
    <meta property="business:contact_data:postal_code" content="<?= PLUGIN_SDATA_POSTALCODE ?>">
<?php
        }
        if (PLUGIN_SDATA_COUNTRYNAME !== '') {
?>
    <meta property="business:contact_data:country_name" content="<?= PLUGIN_SDATA_COUNTRYNAME ?>">
<?php
        }
        if (PLUGIN_SDATA_EMAIL !== '') {
?>
    <meta property="business:contact_data:email" content="<?= PLUGIN_SDATA_EMAIL ?>">
<?php
        }
        if (PLUGIN_SDATA_TELEPHONE !== '') {
?>
    <meta property="business:contact_data:phone_number" content="<?= PLUGIN_SDATA_TELEPHONE ?>">
<?php
        }
        if (PLUGIN_SDATA_FAX !== '') {
?>
    <meta property="business:contact_data:fax_number" content="<?= PLUGIN_SDATA_FAX ?>">
<?php
        }
?>
    <meta property="business:contact_data:website" content="<?= HTTP_SERVER ?>">
    <!-- eof Facebook structured data general-->
<?php
    } else {
?>
    <!-- Facebook structured data for product-->
    <meta property="og:type" content="<?= trim(PLUGIN_SDATA_FOG_TYPE_PRODUCT) ?>">
    <meta property="product:availability" content="<?= (($product_base_stock > 0) ? 'instock' : $facebookAvailability[PLUGIN_SDATA_OOS_DEFAULT]) ?>">
    <meta property="product:brand" content="<?= (isset($manufacturer_name) && trim($manufacturer_name) !== '')
                    ? $manufacturer_name
                    : (defined('STORE_NAME') ? STORE_NAME : '') ?>">
    <meta property="product:category" content="<?= htmlentities($category_name) ?>">
    <meta property="product:condition" content="<?= PLUGIN_SDATA_FOG_PRODUCT_CONDITION ?>">
<?php
        if ($product_base_mpn !== '') {
?>
    <meta property="product:mfr_part_no" content="<?= $product_base_mpn ?>">
<?php
        }
?>
    <meta property="product:price:amount" content="<?= $product_base_displayed_price ?>">
    <meta property="product:price:currency" content="<?= PLUGIN_SDATA_PRICE_CURRENCY ?>">
    <meta property="product:product_link" content="<?= $canonicalLink ?>">
    <meta property="product:retailer" content="<?= !empty(PLUGIN_SDATA_FOG_APPID) ? PLUGIN_SDATA_FOG_APPID : HTTP_SERVER . DIR_WS_CATALOG ?>">
    <meta property="product:retailer_category" content="<?= htmlentities($category_name) ?>">
    <meta property="product:retailer_part_no" content="<?= $product_base_sku ?>">
    <!-- eof Facebook structured data -->
<?php
    }
} //end facebook enabled
?>
<?php
if (PLUGIN_SDATA_TWITTER_CARD_ENABLE === 'true') {
?>
    <!-- Twitter Card markup -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="<?= PLUGIN_SDATA_TWITTER_USERNAME ?>">
    <meta name="twitter:title" content="<?= $title ?>">
    <meta name="twitter:description" content="<?= htmlentities($description) ?>">
<?php
$image = ($image_default ? $image_default_twitter : $image);
?>
    <meta name="twitter:image" content="<?= $image ?>">
    <meta name="twitter:image:alt" content="<?= htmlentities($image_alt, ENT_QUOTES, CHARSET, false) ?>">
    <meta name="twitter:url" content="<?= htmlentities($canonicalLink, ENT_COMPAT, CHARSET, false) ?>">
    <meta name="twitter:domain" content="<?= HTTP_SERVER ?>">
    <!-- eof Twitter Card markup -->
<?php
} //end of Twitter enabled
?>
<?php //google+ markup
if (PLUGIN_SDATA_GOOGLE_PUBLISHER !== '') {
?>
    <!-- Google+-->
    <link href="<?= PLUGIN_SDATA_GOOGLE_PUBLISHER ?>" rel="publisher">
    <!-- eof Google+-->
<?php
} //eof Google+
