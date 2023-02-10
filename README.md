# Structured Data for Zen Cart
Tested on Zen Cart 157/8.
Compatible with php 7.0-8.2

Plugin that adds Schema (in JSON-LD format), Facebook and Twitter structured markup to all pages.
Schema markup is added in three blocks: organisation, breadcrumbs and product (including reviews).

This plugin was originally based on the Super Data code with reviews and breadcrumbs added from Zen 4All Github but my modifications got out of hand and I redid it completely.
I made considerable changes for some bugs, multilanguage site with multibyte characters and removed/added fields as demanded by the various validators.

Plugin Forum Thread:
https://www.zen-cart.com/showthread.php?221868-Structured-Data-Markup-for-Schema-Facebook-Open-Graph-Twitter

## INSTALLATION

As ALWAYS, expect that ANY new code will self-destruct your shop and eat your pets, so test ALL new code on your development server FIRST.
When you are satisfied, ensure your production files and database are backed up prior to installing in the production site. 

Super Data: If you wish to uninstall the old Super Data plugin, please note that the uninstall sql included with that plugin is incorrect. A corrected version is included with these files.

1. BACKUP

2. Use the installation sql to install the constant definitions and register the new admin configuration page into the database.  
In my testing it was possible to run the sql code in the ZC->Admin->SQL Patch tool on a vanilla installation. But it's known to be pretty strict (https://www.zen-cart.com/showthread.php?216551-ERROR-Cannot-insert-configuration_key-quot-quot-because-it-already-exists-empty-db-key), so if this gives you an error, you can restore the database (from the backup you did immediately before trying this...), and try again using phpmyadmin instead. 

3. Copy the single admin file with the configuration menu title, to enable the admin page to display.  
**CHECK THE ADMIN PAGE WORKS BEFORE GOING ANY FURTHER.**  
    The plugin is disabled on installation as **YOU** need to add your site-specific values to the constants and enable it in the configuration page before it will show up in the catalog \<head>.

    Optional

    There are 38 constants and it's very tedious to update them one by one (especially if repeatedly testing the sql install and thereby starting from scratch each time).
	I have included a spreadsheet where you can enter all the constant values into a worksheet to generate sql UPDATE queries.
	Hence you can copy and paste the queries to enter all the values into the database in one go (via the ZC admin SQL patch tool or phpmyadmin).
	
4. Copy the catalog javascript file to: `includes/templates/YOUR_TEMPLATE/jscript`

    The existence of this file in the /jscript folder will include the structured data blocks in ALL pages automagically.

5. Although the markup will display without any further template modifications, strictly you should make this additional modification to the html_header.php, assuming you have a HTML5 template.

    from:
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

6. Additional Constant Definitions in the javascript file.
As bugs surface and additional code is required, I've added extra constants at the start of the file instead of making a comprehensive installer.
**You must read and modify these constants as per your site needs.**

### Availability
If a product is out of stock (oos), there are various statuses to indicate the availability.
https://developers.google.com/search/docs/appearance/structured-data/product

You define your default oos status.  

    define('PLUGIN_SDATA_OOS_DEFAULT', 'BackOrder');

If your products have various possibilities, you'll have to deal with that...
I use Products' Options' Stock Manager (https://vinosdefrutastropicales.com/index.php?main_page=product_info&products_id=46), that allows user-defined out of stock messages for products with attributes. I expanded that to also include simple products (without attributes) and integrated that into this plugin.


### Weight
In the script there is a default weight constant which will be used if a product has no weight defined/weight is zero.
Edit this to your needs.  

    define('PLUGIN_SDATA_DEFAULT_WEIGHT', '0.3'); // fallback weight if product weight in database is not set


### Reviews
Google Rich Results Tool gives warnings about no reviews on a product: 100's of products = 100's of warnings, obscuring any real problems. Tedious.

These two constants are used to prevent that/provide a  review rating in the absence of a real one.

In the script there are two constants

    define('PLUGIN_SDATA_REVIEW_USE_DEFAULT', 'true'); // if no product review, use a default value to stop Google warnings
    define('PLUGIN_SDATA_REVIEW_DEFAULT_VALUE', '3'); // avg. rating (when no product reviews exist)

If you don't want to be naughty and not offer reviews when there are none, set 

    define('PLUGIN_SDATA_REVIEW_USE_DEFAULT', 'false'); // if no product review, use a default value to stop Google warnings
    define('PLUGIN_SDATA_REVIEW_DEFAULT_VALUE', '3'); // avg. rating (when no product reviews exist)
	
### SKU/MPN/GTIN
This section describes adding custom fields to your product table.

**_There is a companion Plugin available to both install custom fields and add the corresponding fields in the admin Product Edit page:_**

https://github.com/torvista/Zen_Cart-Extra_Product_Fields

**_sku:_** is populated by products_model. This is the code used by **_your_** shop, which is probably unique to **_your_** shop.

**_mpn:_** is the original **_manufacturers part number_**. It is unlikely that you are using that as your shop sku, so you will need to add this column to your products table and populate it.

ALTER TABLE `products` ADD `products_mpn` VARCHAR(32) NOT NULL DEFAULT '';

**_gtin:_** international identification number that depends on the products you sell: UPC / GTIN-12 / EAN / JAN / ISBN / ITF-14. 
You'll need to deal with this similarly to mpn.

I used ean and added a products_ean field to the products table:

ALTER TABLE `products` ADD `products_ean` VARCHAR(13) NOT NULL DEFAULT '';  

In the code, you will need to modify the code to use the column names you have created: the necessary sections for modification are marked CUSTOM CODING.

By default, they are left unpopulated so Google Rich Results Tool will remind you they are missing.

### Google Product Category
Google has defined its own set of numbers to categorise products.

Taxonomy here: https://support.google.com/merchants/answer/6324436?hl=en

If all your products belong to the same category, there is no need to add another column to the products table: in the script, set PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY to your category number.

By default, it is left blank to generate warnings for you to deal with.

If your products fall into different categories, you will need to add a new column in the product table to store a category per product.

ALTER TABLE `products` ADD `products_google_product_category` VARCHAR(6) NOT NULL DEFAULT '';

Products without any specific category defined will use the value in PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY.

### Attributes
Vanilla Zen Cart does not have provision for attribute sku nor stock control, only prices.
So, the 'default' handling of attributes will only provide an aggregateOffer in "offers": separating out each attribute would only generate more Google Rich Results warnings as no sku/mpn/gtin can be provided.

### Third-party attribute-stock plugins
#### Products Options Stock Manager (POSM)

I use this plugin, so have added the code necessary to deal with one attribute (dependent attributes are pending/too hard).

However, it still requires extra fields adding per attribute:

ALTER TABLE `products_options_stock` ADD `pos_mpn` VARCHAR(32) NOT NULL DEFAULT '' AFTER `pos_model`;

ALTER TABLE `products_options_stock` ADD `pos_ean` VARCHAR(13) NOT NULL DEFAULT '' AFTER `pos_mpn`;

You may choose to use something other than ean.

The code is written and commented to allow the easy addition of other plugins that handle attribute-stock such as Stock by Attributes....but you will have to do that and push the changes to GitHub for inclusion.

----------------

## Check Your Output

Check the output in the head on all your pages for empty parameters or properties that don't reflect what they should.

Every site is different, so it is impossible to make this plugin 100% plug and play, you DO need to check the markup output carefully to ensure it reflects your business and be prepared to modify accordingly or report any omissions if you think they are relevant generally.

 Use the various debuggers to check the various blocks:
 
 - Google Rich Results Test
 - Facebook Opengraph Debugger
 - Twitter Card Validator

If things are not what you think they should be, or you are getting errors in the tools, please report the issue on GitHub.

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
See GitHub History  
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
