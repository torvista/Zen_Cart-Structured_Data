# Structured Data for Zen Cart
Tested on Zen Cart 2.1.0+ on php 8+

Encapsulated Plugin that adds Schema (in JSON-LD format), Facebook and Twitter structured markup to all pages.
Schema markup is added in three blocks: organisation, breadcrumbs and product (including reviews).

Plugin Forum Thread:  
https://www.zen-cart.com/showthread.php?221868-Structured-Data-Markup-for-Schema-Facebook-Open-Graph-Twitter

GitHub:  
https://github.com/torvista/Zen_Cart-Structured_Data

## INSTALLATION
As ALWAYS, expect that ANY new code will self-destruct your shop and eat your pets, so test ALL new code on your development server FIRST: consider your production site (which you don't touch much) as a backup for your development site (which you modify continually...).

When you are satisfied, ensure your production files and database are backed up prior to installing in the production site. 

1. Backup any previous version of Structured Data.  
The previous version contained constants defined in the script: YOUR/TEMPLATE/jscript/jscript_structured_data.php  
Make a copy of this file for reference.

1. BACKUP DATABASE: the install _should_ retain all your previous settings...but in case, you have been warned twice now.

1. Copy contents of /files/zc_plugins to the corresponding folder in your development site and Install it.  
If you have a prior installation of Structured Data it will remove those files and add new constants which were previously defined in the script.

## Configuration
1. Admin->Configuration->Structured Data  
Add your site-specific values.  
** You MUST read and modify these constants, they are SITE-SPECIFIC. **  
Refer to Google and Schema pages for further explanation.  
Optional Setup by spreadsheet.  
There are 53 constants added into the Admin and it's very tedious to update them one by one (especially if repeatedly testing the sql install and thereby starting from scratch each time).  
You may use /docs/Structured_Data-Bulk_UPDATE_sql to enter all the constant values into a worksheet and generate a set sql UPDATE queries to update all the values into the database in one go (via the ZC admin SQL patch tool or phpmyadmin).

1. Although the markup will display without any further template modifications, strictly you should make this additional modification to the html_header.php, assuming you have a HTML5 template. 
 
From:
```php
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
```  
to:
```php
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?> prefix="og: https://ogp.me/ns# fb: https://ogp.me/ns/fb# product: https://ogp.me/ns/product#">
```
This adds the namespaces for the properties og:, fb:, product: which are used later in the structured data block.

## Check Your Output

Check the output in the head on all your pages for empty parameters or properties that don't reflect what they should.

Every site is different, so it is impossible to make this plugin 100% plug and play, you DO need to check the markup output carefully to ensure it reflects your business and be prepared to modify accordingly or report any omissions if you think they are relevant generally.

 Use the various debuggers to check the various blocks:
 
 - Google Rich Results Test
 - Facebook Opengraph Debugger
 - Twitter Card Validator

If things are not what you think they should be, or you are getting errors in the tools, please report the issue on GitHub.

## Addition Information on the Parameters

### Availability
If a product is out of stock (oos), there are various statuses to indicate the availability.
https://developers.google.com/search/docs/appearance/structured-data/product

You define your default oos status.  

    define('PLUGIN_SDATA_OOS_DEFAULT', 'BackOrder');

The core plugin Products' Options' Stock Manager allows user-defined out of stock messages for products with attributes. 

### Call for Price
If a product is Call for Price, the price is set to zero...if price is  missing, it is invalid data.
https://support.google.com/webmasters/thread/2444180/schema-mark-up-offers-when-price-only-available-upon-request?hl=en

If product has attributes, these are skipped completely.

### Returns Policy
There are constants for the limit (days) for returning a product, the cost if non-zero and the method.

The applicableCountry attribute is for the country FROM WHICH the product is to be returned, so the policy (time-restiction/cost) applies to THAT one country.
I could not find a way to use multiple countries for the same policy, nor multiple policies for multiple countries.

### Reviews
If a product has no reviews, Google Rich Results Tool gives warnings: 100's of products = 100's of warnings, obscuring any real problems. Tedious.

In this case an optional dummy review rating is supplied to prevent those warnings. The date of the dummy review is the product creation.

### Weight
In the script there is a default weight constant which will be used if a product has no weight defined/weight is zero.

### SKU/MPN/GTIN
This section describes adding custom fields to your product table.

**_There is a companion Plugin available to both install custom fields and add the corresponding fields in the admin Product Edit page:_**

https://github.com/torvista/Zen_Cart-Extra_Product_Fields

**_sku:_** is populated by products_model. This is the code used by **_your_** shop, which is probably unique to **_your_** shop.


**_gtin:_** international identification number that depends on the products you sell: UPC / GTIN-12 / EAN / JAN / ISBN / ITF-14. 

I used gtin and added a products_gtin field to the products table:

ALTER TABLE `products` ADD `products_gtin` VARCHAR(13) NOT NULL DEFAULT '';  

Set the name of your custom GTIN field in the Admin.

By default, they are left unpopulated so Google Rich Results Tool will remind you they are missing.

### Google Product Category
Google has defined its own set of numbers to categorise products.

Taxonomy here: https://support.google.com/merchants/answer/6324436?hl=en

If all your products belong to the same category, there is no need to add another column to the products table: in the script, set PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY to your category number.

By default, it is left blank to generate warnings for you to deal with.

If your products fall into different categories, you will need to add a new column in the product table to store a category per product.

ALTER TABLE `products` ADD `products_google_product_category` VARCHAR(6) NOT NULL DEFAULT '';

Set the name of your custom GPC field in the Admin.

Products without any specific category defined will use the value in PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY.

Code to allow the admin editing of these values is out of the scope of this plugin.

### Attributes
Attributes handling info: https://www.schemaapp.com/newsletter/schema-org-variable-products-productmodels-offers/#

Historically, Zen Cart had no attribute-stock control so the 'default' handling of attributes only provides an aggregateOffer in "offers": separating out each attribute would only generate more Google Rich Results warnings as no sku/mpn/gtin can be provided.

Zen Cart now includes a plugin POSM for attribute-stock control. However it does not include fields for mpn, GTIN for each attribute combination.

I have added the code necessary to deal with one attribute (dependent attributes are pending...).  
However, extra fields are required to provide mpn/GTIN per attribute.

e.g.

ALTER TABLE `products_options_stock` ADD `pos_mpn` VARCHAR(32) NOT NULL DEFAULT '' AFTER `pos_model`;

ALTER TABLE `products_options_stock` ADD `pos_gtin` VARCHAR(13) NOT NULL DEFAULT '' AFTER `pos_mpn`;

You may choose to use something other than gtin.

Code to allow the admin editing of these values is out of the scope of this plugin.

The script code is written and commented to allow the addition of other plugins that handle attribute-stock such as Stock by Attributes...but you will have to do that yourself.

## USAGE
Things behind some of the code that you may wish to modify/be aware of.

### SCHEMA

There are three code blocks:

1. Organisation: on all pages.

    The `sameAs` property should point to: 
 	
    a. the "best" page for contact information on this particular website: this is currently hard-coded as the contact_us page. The property is thus not generated on the contact_us page.

    b. other social pages

2. `Breadcrumbs`: on all pages
3. `Product`: on product pages only

priceValidUntil: is set to the last day of the current year as that may be when prices get updated.

#### Organisation or LocalBusiness?

`LocalBusiness` - refers to a PHYSICAL store NOT an online-only shop. Then you can add a subtype such as Store or something more specific from the spreadsheet listing here:
https://docs.google.com/spreadsheets/d/1Ed6RmI01rx4UdW40ciWgz2oS_Kx37_-sPi7sba_jC3w/edit?pli=1#gid=0

An online-only business should use `Organisation`, (or the more specific `Corporation`, if it applies). This allows you to use the `makesOffer`/`offeredBy` property.

If you wish to use `LocalBusiness`, this may be expanded (example):
  "openingHours": "Mo, Tu, We, Th, Fr 9:00-17:00"

#### Image Size
To suit all, a minimum of 600x300 is recommended.
 
### Google/Schema
Image size is not defined in Schema but one Google recommendation is
minimum:160x90
maximum: 1920x1080

### Facebook
Google Structured Data Testing tools complains about "Unspecified Type".
Ignore it, discussed here:
https://productforums.google.com/forum/#!msg/webmasters/tOewAWTfMDM/l8ZYnmk-BQAJ

Tags have been referenced to type product:
https://developers.facebook.com/docs/reference/opengraph/object-type/product/
and NOT product.item:
https://developers.facebook.com/docs/reference/opengraph/object-type/product.item/

some tags are different...

Note that an error in the OG debugger will stop the app id from showing up in for example scraped tags, even though you would assume it to be unrelated to the error...so fix the reported error!

#### `og:image`
There must be a type for each image.

minimum dimensions (or will not display): 200*200px or will not be shown

maximum dimensions: 1200/630px

"recommended" dimensions by users: 600/315 (1.91:1)

#### `Type`
`Type` may need editing to suit your business.
<meta property="og:type" content="business.business" />

Background info for type business.business:
https://developers.facebook.com/docs/reference/opengraph/object-type/business.business

#### `Condition`
`condition`: new, refurbished, used. The Schema definitions are slightly different, so they are hard-coded/listed in an array in the code.

The availability of the product is `instock`, `oos`, or `pending`. Hardcoded to `instock` and `pending` depending on if stock = 0 or not.

#### `Product` 
- `availability`: set to `inStock` or `PreOrder`
- `deliveryLeadTime`: set to `inStock` = 1 day, `Out of Stock/PreOrder` = 7 days
- `mpn`, `gtin`: not displayed by default. Strictly, they are not the retailers model/SKU code and need to be stored/retrieved by custom coding.
 
### Twitter

minimum dimensions =: 280x150px or will not display 

maximum size: approx. 1MB.

"recommended" dimensions by users: 600x321 (1.867:1)

## Changelog
See commit history for further changes

2025 02 11 - Convert to encapsulated plugin, update of install and spreadsheet with script-based additional constants.
2025 02 10 - torvista:
modify reviewsArray name to prevent conflict with reviews on product page.

2023 09 04 - torvista:
bugfix: allow for core variable $reviewsArray on the product review page.

2023 05 31 - torvista:
added hasMerchantReturnPolicy

2023 02 10 - torvista:
truncate name and descriptions to Google limits, Added Item Availability for out of stock status

2021 03 31 - torvista: added support for google product category
added test values to spreadsheet

2020 11 02 - torvista: added support for attributes (default and Product Options Stock plugin), 
corrected typo PLUGIN_SDATA_PRICE_CURRRENCY to PLUGIN_SDATA_PRICE_CURRENCY

2020 10 28 - torvista: separated sku, mpn, gtin entries

2020 03 27 - torvista: Added the option of creating an anonymous, blank review with an admin-defined star-rating for products with no review at all, in an attempt to stop hundreds of warnings, Missing field "aggregateRating", 
Missing field "review"

2020 02 25 - torvista: changed array declarations to short syntax, changed while to a foreach

2020 02 11 - torvista: fixed double encoding ampersands, general revision for strict mode/EA inspection recommendations

2019 06 18 - torvista: review for php notices, facebook reviews code to only run on a product page.

2019 05 08 - torvista: added schema: priceValidUntil (used last day of the year), 
added schema: SKU. sku, mpn, ProductID all use the same products_model. Moved fields around to mirror Google Structured Data Tool example, edited the layout spacing for better visual presentation.

Bugfix
Improved cleaning of product description for schema (line feeds, carriage returns, extra spaces).
meta property="og:description" was always using Meta Tag description instead of category description when defined.

2018 10 03 - torvista: Minor readme corrections. Uploaded to Zen Cart Plugins as v1.0

2017 04 24 - Dr. Byte: improved readme (and further modified by torvista to use Github markdown), install sql, trap error for getimagesize

2017 04 - torvista: changed review, datepublished to required format yyy-mm-dd
bugfix: corrected product, offer, acceptedPaymentMethods

2017 02 - torvista: og: product_retailer changed from AdminID to Appid
og: product:retailer_title removed (not in spec)

complete overhaul...
I made considerable changes for some bugs, multilanguage site with multibyte characters and removed/added fields as demanded by the various validators.

revised Super Data, breadcrumb code and added/revised Review code for products from Zen4all:
https://github.com/Zen4All-nl/Zen-Cart-Structured-Data-using-Json

Many changes made so decided to offer this as a separate plugin.
bugs: added closing spaces to some properties, added missing quotation marks around product: acceptedPaymentMethod, incorrect category name on product, reviews all used same rating, review bestRating/worstRating incorrectly used, reviews did not take account of a multi-language shop nor multibyte characters in names and descriptions. sameAS item assumed all constants populated (not true) so produced an invalid listing with spaces and quotation marks.
Facebook:
condition->product.condition and others added/modified. App secret removed: not used anywhere.
        <meta property="og:email" content="<?php echo FACEBOOK_OPEN_GRAPH_EMAIL; ?>" /> Deprecated
images: added optional image tags
locale: added array to auto-generate locale and locale:alternate
Twitter: removed obsolete card types. Changed to large_card.
Changed: removed sql queries where not required - got info from other already existing globals. Added simple stock/lat9 POSM query. Added clauses around facebook and twitter markup to only display if using these services. Image info re-written.
Removed attributes: product: inventoryLevel 

2015 Zen Cart - forked into Super Data - mprough
https://www.zen-cart.com/downloads.php?do=file&id=1984

2014 Zen Cart - Facebook Open Graph - Numinix
https://www.zen-cart.com/downloads.php?do=file&id=1820
