# Structured-Data-for-Zen-Cart
Plugin that adds Schema (in JSON-LD format), Facebook and Twitter structured markup to all pages.
Schema markup is added in three blocks : organisation, breadcrumbs and product (including reviews).

This plugin was originally based on the Super Data code with reviews and breadcrumbs added from Zen 4All Github but my modifications got out of hand and I redid it completely.
I made considerable changes for some bugs, multilanguage site with multibyte characters and removed added fields as demanded by the various validators.

#INSTALLATION

1. Test entire process on your development server...


 Note that if you wish to uninstall the old Super Data plugin, the uninstall sql included with that plugin is incorrect. A corrected version is included with these files.

2. Install constants and register admin page into database using installation sql.

 a. In my testing it was possible to use the ZC->Admin->SQL Patch tool on a ZC155d vanilla installation.

 But on a real db, this may give you an error, in which case do it via phpmyadmin.  (https://www.zen-cart.com/showthread.php?216551-ERROR-Cannot-insert-configuration_key-quot-quot-because-it-already-exists-empty-db-key)


 b. Optional. There are 38 constants and it can be a pain to update them one by one especially if repeatedly testing the sql install.
 There is a spreadsheet included where you can enter all the parameters on a worksheet and copy all the generated sql UPDATE statements into the ZC admin SQL patch tool/phpmyadmin for a quick update.

3. Copy the admin file to enable the admin page to display.
CHECK THE ADMIN PAGE WORKS BEFORE GOING FURTHER.

4. Copy catalog file to: `includes/templates/YOUR_TEMPLATE/jscript`

 This should make the Structured Data be included on all pages automagically.

5. Although the markup should work without any template modifications, strictly you should add this to the html_header.php, assuming you have a html5 template.

 from:
```php
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?>>
```
to:
```php
<!DOCTYPE html>
<html <?php echo HTML_PARAMS; ?> prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb# product: http://ogp.me/ns/product#">
```

 This adds the namespaces for the properties og:, fb:, product:

6. Check the output on all your pages that there are no empty parameters or properties that don't reflect what they should.

 Use the various debuggers:
 - Google Structured Data
 - Facebook Opengraph Debugger
 - Twitter Card Validator

Please try it out and report any issues.
It is impossible to make a plugin perfect for all sites so be prepared to modify it to your specific case or report the omissions.

#USAGE
Things behind some of the code that you may wish to modify/be aware of.

##SCHEMA

There are three code blocks:

1. Organisation: on all pages.

 The `sameAs` property should point to: 
 	
 a. the "best" page for contact information on this particular website: this is currently hard-coded as the contact_us page and is removed on the contact_us page.


 b. other social pages
2. Breadcrumbs: on all pages
3. Product: on product pages

##Organisation or LocalBusiness?

`LocalBusiness` - refers to a PHYSICAL store NOT an online only shop. Then you can add a subtype such as Store or something more specific from the spreadsheet listing here:
https://docs.google.com/spreadsheets/d/1Ed6RmI01rx4UdW40ciWgz2oS_Kx37_-sPi7sba_jC3w/edit?pli=1#gid=0

An online-only business should use `Organisation`, (or the more specific `Corporation`, if it applies). This allows you to use the `makesOffer`/`offeredBy` property.

If you wish to use LocalBusiness, this may be enabled (example):
  "openingHours": "Mo, Tu, We, Th, Fr 9:00-17:00"

##Image Size
To suit all, a minimum of 600x300 is recommended.
 
###Google/Schema
Image size is not defined in Schema but one Google recommendation is
minimum:160x90
maximum: 1920x1080

##Facebook
Google Structured Data Testing tools complains about "Unspecified Type".
Ignore it, discussed here:
https://productforums.google.com/forum/#!msg/webmasters/tOewAWTfMDM/l8ZYnmk-BQAJ

tags have been referenced to type product:
https://developers.facebook.com/docs/reference/opengraph/object-type/product/
and NOT product.item:
https://developers.facebook.com/docs/reference/opengraph/object-type/product.item/
some tags are different...

Note that an error in the OG debugger will stop the app id from showing up in the example of the scraped tags, even though you would assume it to be unrelated to the error...so fix the reported error!

##og:image
There must be a type for each image.

minimum dimensions (or will not display): 200*200px or will not be shown

maximum dimensions: 1200/630px

"recommended" dimensions by users: 600/315 (1.91:1)

##Type
`Type` may need editing to suit your business.
<meta property="og:type" content="business.business" />

Background info for type business.business:
https://developers.facebook.com/docs/reference/opengraph/object-type/business.business

##Condition
`condition`: new, refurbished, used. The Schema definitions are slightly different so they are hard-coded/listed in an array in the code.

The availability of the product is `instock`, `oos`, or `pending`. Hardcoded to `instock` and `pending` depending on if stock = 0 or not.

##Product 
- availability: `inStock` or `PreOrder`
- deliveryLeadTime: `inStock` = 1 day, `Out of Stock/PreOrder` = 7 days
				
##Twitter

minimum dimensions (or will not display=: 280x150px or will not be shown 

maximum size: approx 1MB.

"recommended" dimensions by users: 600x321 (1.867:1)


#Changelog

2017 04 - torvista
changed review, datepublished to required format yyy-mm-dd
bugfix: corrected product, offer, acceptedPaymentMethods

2017 02 - torvista
og: product_retailer changed from AdminID to Appid
og: product:retailer_title removed (not in spec)

complete overhaul...
revised Super Data, breadcrumb code and added/revised Review code for products from Zen4all:
https://github.com/Zen4All-nl/Zen-Cart-Structured-Data-using-Json

Many changes made so decided to offer this as a separate plugin.
bugs: added closing spaces to some properties, added missing quotation marks around product: acceptedPaymentMethod, incorrect category name on product, reviews all used same rating,  review bestRating/worstRating incorrectly used, reviews did not take account of a multi-language shop nor multibyte characters in names and descriptions. sameAS item assumed all constants populated (not true) so produced an invalid listing with spaces and quotation marks.
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
