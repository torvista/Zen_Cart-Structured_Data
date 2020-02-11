<?php
/* THIS FILE MUST BE LOADED IN html <head> SINCE IT USES meta tags.
 * DO NOT RE-FORMAT THE CODE: it is structured so the html seen in Developer Tools Inspector looks logical.
 * 2020 11 02 torvista
 *
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

if (defined('PLUGIN_SDATA_ENABLE') && PLUGIN_SDATA_ENABLE === 'true') {
    //defaults (subsequently overwritten) defined to prevent php notices
    $description = '';
    $title = '';
    $image = '';
    $image_alt = '';
    $image_default = false;
    $facebook_type = 'business.business';

    //product condition mapping for Schema
    $itemCondition_array = array('new' => 'NewCondition', 'used' => 'UsedCondition', 'refurbished' => 'RefurbishedCondition');

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

    $is_product_page = ($current_page_base === 'product_info' && !empty($_GET['products_id'] && zen_products_lookup($_GET['products_id'], 'products_status') === '1'));
    if ($is_product_page) {//product page only
        //get product info
        $sql = 'SELECT p.products_id, p.products_model, pd.products_name, pd.products_description, p.products_quantity, p.products_image, p.products_price, p.products_tax_class_id
           FROM ' . TABLE_PRODUCTS . ' p, ' . TABLE_PRODUCTS_DESCRIPTION . ' pd
           WHERE p.products_id = ' . (int)$_GET['products_id'] . '
           AND pd.products_id = p.products_id
           AND pd.language_id = ' . (int)$_SESSION['languages_id'];
        $product_info = $db->Execute($sql);
        $product_id = $product_info->fields['products_id'];
        $product_name = $product_info->fields['products_name'];
        $title = htmlspecialchars(STORE_NAME . ' - ' . $product_info->fields['products_name']);
        $product_model = $product_info->fields['products_model'];
        $description = $product_info->fields['products_description'];//variable used in twitter for categories & products
        $tax_class_id = $product_info->fields['products_tax_class_id'];
        $manufacturer_name = zen_get_products_manufacturers_name((int)$_GET['products_id']);
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
            }//Image Handler in use
            $image = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . $product_image;
        } else {//no image defined in product info
            //note PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE is a FULL path with protocol
            $image = (PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE !== '' ? PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE : HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . PRODUCTS_IMAGE_NO_IMAGE);//if no default image, use standard no-image file.
        }

        //stock level
        $stock = $product_info->fields['products_quantity'];
        //is lat9 Products Options Stock Manager installed?
        if (function_exists('is_pos_product') && is_pos_product($product_id)) {//POSM manages stock of this product
            $posm_stock_tax_sql = 'SELECT pos.pos_id, pos.products_quantity FROM ' . TABLE_PRODUCTS . ' p LEFT JOIN ' . TABLE_PRODUCTS_OPTIONS_STOCK . ' pos ON p.products_id = pos.products_id WHERE p.products_id = ' . $product_id;
            $posm_stock_tax_result = $db->Execute($posm_stock_tax_sql);
            foreach ($posm_stock_tax_result as $row) {
                $stock = +$row['products_quantity'];//sums POSM (attributes) stocks. Doesn't matter if $stock was set previously: if POSM-managed it would be zero anyway. Not critical!
            }
        }

        //get the price with tax
        $product_display_price_value = round(zen_get_products_actual_price($product_id) * (1 + zen_get_tax_rate($tax_class_id) / 100), 2);//show price with tax, decimal point (not comma), two decimal places

        $category_id = zen_get_products_category_id($product_id);
        $category_name = zen_get_categories_name($category_id);
        $image_alt = $product_name;
        $facebook_type = 'product';
    } elseif (isset($_GET['cPath'])) {//NOT a product page
        $cPath_array = explode('_', $_GET['cPath']);
        $category_id = end($cPath_array);
        reset($cPath_array);
        $category_name = zen_get_categories_name($category_id);
        if ($category_name !== '') { //a valid category
            $category_image = zen_get_categories_image($category_id);
            if ($category_image === '') {
                $image_default = true;
            } else {
                $image = HTTP_SERVER . DIR_WS_CATALOG . DIR_WS_IMAGES . zen_get_categories_image($category_id);
            }
            $description = zen_get_category_description($category_id, (int)$_SESSION['languages_id']) !== '' ? zen_get_category_description($category_id, (int)$_SESSION['languages_id']) : META_TAG_DESCRIPTION;
            $product_category_name = $category_name;//used for twitter title, changes depending if page is product or category
            $image_alt = $category_name;
            $facebook_type = 'product.group';
            $title = META_TAG_TITLE;
        }
    } else {//some other page - not product or category, maybe a bad cPath https://github.com/zencart/zencart/issues/2903
        $image_default = true;
        $breadcrumb_this_page = isset($breadcrumb->_trail[count($breadcrumb->_trail) - 1]['title']) ? $breadcrumb->_trail[count($breadcrumb->_trail) - 1]['title'] : '';
        $image_alt = $breadcrumb_this_page;
        $title = META_TAG_TITLE;
        //$title = $breadcrumb_this_page;
        $description = META_TAG_DESCRIPTION;
        $facebook_type = 'business.business';
    }

    //torvista: my site only, using boilerplate text!
    if (function_exists('mv_get_boilerplate')) {
        $description = mv_get_boilerplate($description, $descr_stringlist);
    }
    //eof

    //clean $description
    $description = htmlentities(strip_tags(trim($description)));//remove tags
    $description = str_replace(array("\r\n", "\n", "\r"), '', $description);//remove LF, CR
    $description = preg_replace('/\s+/', ' ', $description);//remove multiple spaces

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
    }
    unset($profile_page);
    $sameAs = implode(",\n", $sameAs_array);

    //build acceptedPaymentMethod list
    $PaymentMethod_array = explode(', ', PLUGIN_SDATA_ACCEPTED_PAYMENT_METHODS);
    foreach ($PaymentMethod_array as &$payment_method) {
        $payment_method = '"http://purl.org/goodrelations/v1#' . $payment_method . '"';
    }
    unset($payment_method);
    $PaymentMethods = implode(",\n", $PaymentMethod_array);

    //build Facebook locales
    $locales_array = explode(',', PLUGIN_SDATA_FOG_LOCALES);
    if (count($locales_array) > 1 && (count($locales_array) % 2 === 0)) {//more than one value and is actually a pair
        $locales_keys_array = array();
        $locales_values_array = array();
        $i = 0;
        while ($i < count($locales_array)) {
            $locales_keys_array [] = $locales_array[$i];
            $i += 2;
        }
        $i = 1;
        while ($i < count($locales_array)) {
            $locales_values_array [] = $locales_array[$i];
            $i += 2;
        }
        $locales_array = array_combine($locales_keys_array, $locales_values_array);
        if (array_key_exists($_SESSION['languages_id'], $locales_array)) {
            $locale = $locales_array[$_SESSION['languages_id']];
        }
        unset($locales_array[$_SESSION['languages_id']]);
    }

    //reviews
    if ($current_page_base === 'product_info' && isset($_GET['products_id'])) {
        $reviewQuery = 'SELECT r.reviews_id, r.customers_name, r.reviews_rating, r.date_added, r.status, rd.reviews_text
                FROM ' . TABLE_REVIEWS . ' r
                LEFT JOIN ' . TABLE_REVIEWS_DESCRIPTION . ' rd ON rd.reviews_id = r.reviews_id
                WHERE products_id = ' . (int)$_GET['products_id'] . '
                AND status = 1
                AND languages_id= ' . $_SESSION['languages_id'] . '
                ORDER BY reviews_rating DESC';
        $review = $db->Execute($reviewQuery);
        while (!$review->EOF) {
            $reviewArray[] = array(
                'reviewId' => $review->fields['reviews_id'],
                'customerName' => $review->fields['customers_name'],
                'reviewRating' => $review->fields['reviews_rating'],
                'dateAdded' => $review->fields['date_added'],
                'reviewText' => $review->fields['reviews_text']
            );
            $review->MoveNext();
        }
        $ratingSum = 0;
        $ratingValue = 0;
        $reviewCount = 0;
        if (isset($reviewArray) && is_array($reviewArray)) {
            $reviewCount = count($reviewArray);
            foreach ($reviewArray as $row) {
                $ratingSum += $row['reviewRating'];
            }
            $ratingValue = round($ratingSum / $reviewCount, 1);
        }
    }
    ?>
<?php if (PLUGIN_SDATA_SCHEMA_ENABLE === 'true') { ?>
<script title="Structured Data: schemaOrganisation" type="application/ld+json">
{
     "@context": "http://schema.org",
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
       "@context": "http://schema.org",
          "@type": "BreadcrumbList",
"itemListElement": [
<?php
                foreach ($breadcrumb as $key => $value) {
                    for ($i = 0, $n = count($value); $i < $n; $i++) {
                        if (isset($value[$i]['title']) && zen_not_null($value[$i]['title'])) {//if non-existent url used, title is null: php notice)
                        ?>
      {
      "@type": "ListItem",
   "position": <?php echo $i + 1; //does not need to be quoted ?>,
       "item": {
           "@id": "<?php echo $value[$i]['link']; ?>",
          "name": <?php echo json_encode($value[$i]['title']) . "\n"; ?>
               }
       }<?php if ($i + 1 !== $n) { ?>,
<?php }?>
<?php } //end of zen_not_null
   }//end of For
 }//end of ListItem ?>

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
        "sku": <?php echo json_encode($product_model); //The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers ?>,
        "mpn": <?php echo json_encode($product_model); //The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers. ?>,
      "brand": <?php echo json_encode($manufacturer_name); ?>,
  "productID": <?php echo json_encode($product_model); //The product identifier, such as ISBN. ?>,
     "offers": {
                "@type" : "Offer",
                   "url": "<?php echo $canonicalLink; ?>",
        "priceCurrency" : "<?php echo PLUGIN_SDATA_PRICE_CURRRENCY; ?>",
                "price" : "<?php echo $product_display_price_value; ?>",
      "priceValidUntil" : "<?php echo date("Y") . '-12-31'; //eg 2020-12-31 NOT 2020-31-12: The date after which the price is no longer available. ?>",
        "itemCondition" : "http://schema.org/<?php echo $itemCondition_array[PLUGIN_SDATA_FOG_PRODUCT_CONDITION]; ?>",
         "availability" : "<?php echo ($stock > 0 ? 'http://schema.org/InStock' : 'http://schema.org/PreOrder'); ?>",
               "seller" : <?php echo json_encode(STORE_NAME); //json_encode adds external quotes as the other entries"?>,
     "deliveryLeadTime" : "<?php echo ($stock > 0 ? PLUGIN_SDATA_DELIVERYLEADTIME : PLUGIN_SDATA_DELIVERYLEADTIME_OOS); ?>",
             "category" : <?php echo json_encode($category_name); ?>,
          "itemOffered" : <?php echo json_encode($product_name); ?>,
<?php if (PLUGIN_SDATA_ELIGIBLE_REGION !== '') { ?>       "eligibleRegion" : "<?php echo PLUGIN_SDATA_ELIGIBLE_REGION . '",' . "\n"; } ?>
"acceptedPaymentMethod" : {
                  "@type" : "PaymentMethod",
                   "name" : [<?php echo $PaymentMethods; ?>]
                          }
               }
<?php if ( $reviewCount > 0 ) { //do not bother if no reviews at all. Note note best/worstRating is for the max and min rating used in this review system. Default is 1 and 5 so no need to be declared ?>
  ,
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?php echo $ratingValue; //average rating based on all reviews ?>",
    "reviewCount": "<?php echo $reviewCount; ?>"
  },
  "review" : [
  <?php for ($i = 0, $n = count($reviewArray); $i<$n; $i ++) { ?>
  {
    "@type" : "Review",
    "author" : {
      "@type" : "Person",
      "name" : <?php echo json_encode(strtok($reviewArray[$i]['customerName']," ")); //steve to use only the forename, encoded, does NOT need enclosing quotation marks ?>
    },
    "reviewBody" : <?php echo json_encode($reviewArray[$i]['reviewText']); //steve added json_encode to catch quotation marks and pesky accents etc., does NOT need enclosing quotation marks ?>,
    "datePublished" : "<?php echo substr($reviewArray[$i]['dateAdded'], 0, 10); ?>",
    "reviewRating" : {
      "@type" : "Rating",
      "ratingValue" : "<?php echo $reviewArray[$i]['reviewRating']; //steve bug: was fixed at $ratingValue ?>"
      }
    }<?php if ($i+1 !== $n) { ?>,<?php } ?>
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
<meta property="og:url" content="<?php echo $canonicalLink; ?>" />
<meta property="og:locale" content="<?php echo $locale ?>" />
<?php if ( count($locales_array) > 0 ){
foreach($locales_array as $key=>$value){ ?>
<meta property="og:locale:alternate" content="<?php echo $value; ?>" />
<?php }
} ?>
<?php $image = ($image_default ? $image_default_facebook : $image); ?>
<meta property="og:image" content="<?php echo $image; ?>" />
<meta property="og:image:url" content="<?php echo $image; ?>" />
<?php
    if (is_readable(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image))) {
      $image_info = getimagesize(str_replace(HTTP_SERVER . DIR_WS_CATALOG, '', $image));
?>
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
<meta property="og:type" content="<?php echo PLUGIN_SDATA_FOG_TYPE_PRODUCT; ?>" />
<meta property="product:availability" content="<?php if ($stock > 0) { ?>instock<?php } ?><?php if ($stock < 1) { ?>pending<?php } ?>" />
<meta property="product:brand" content="<?php echo $manufacturer_name; ?>" />
<meta property="product:category" content="<?php echo $category_name; ?>" />
<meta property="product:condition" content="<?php echo PLUGIN_SDATA_FOG_PRODUCT_CONDITION; ?>" />
<meta property="product:mfr_part_no" content="<?php echo $product_model; ?>" />
<meta property="product:price:amount" content="<?php echo $product_display_price_value; ?>" />
<meta property="product:price:currency" content="<?php echo PLUGIN_SDATA_PRICE_CURRRENCY; ?>" />
<meta property="product:product_link" content="<?php echo $canonicalLink; ?>" />
<meta property="product:retailer" content="<?php echo PLUGIN_SDATA_FOG_APPID; ?>" />
<meta property="product:retailer_category" content="<?php echo $category_name; ?>" />
<meta property="product:retailer_part_no" content="<?php echo $product_model; ?>" />
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
<meta name="twitter:image:alt" content="<?php echo htmlentities($image_alt, ENT_QUOTES, 'UTF-8'); ?>" />
<meta name="twitter:url" content="<?php echo htmlentities($canonicalLink); ?>" />
<meta name="twitter:domain" content="<?php echo HTTP_SERVER; ?>" />
<!-- eof Twitter Card markup -->
<?php } //end of Twitter enabled ?>
<?php //google+ markup
if (PLUGIN_SDATA_GOOGLE_PUBLISHER !== '') { ?>
<!-- Google+-->
<link href="<?php echo PLUGIN_SDATA_GOOGLE_PUBLISHER; ?>" rel="publisher" />
<!-- eof Google+--><?php } //eof Google+ ?>
<?php } ?>