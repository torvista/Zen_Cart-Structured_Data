<?php

declare(strict_types=1);

/**
 * @author: torvista
 * @link: https://github.com/torvista/Zen_Cart-Structured_Data
 * @license https://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version torvista 08 Feb 2025
 */
use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected string $configPageKey = 'configStructuredData';

    protected string $configGroupTitle = 'Structured Data';

    protected int $cgi;

    protected string $template_dir;

    /**
     * @return bool
     */
    protected function executeInstall(): bool
    {
        global $template_dir;
        $this->template_dir = $template_dir;

        if (!$this->purgeOldFiles()) {
            return false;
        }

        $this->cgi = $this->getOrCreateConfigGroupId($this->configGroupTitle, $this->configGroupTitle, null);

        // -----
        // If the plugin's configuration settings aren't present, add them now.
        //
        $this->executeInstallerSql(
            "INSERT IGNORE INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function)
             VALUES
                ('Enable Structured Data generation', 'PLUGIN_SDATA_ENABLE', 'true', 'Enable the Structured Data plugin code', $this->cgi, 1, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
                ('Enable Schema markup', 'PLUGIN_SDATA_SCHEMA_ENABLE', 'true', 'Show Schema markup?<br>Shows JSON-LD blocks for Organisation and Breadcrumbs on all pages, Product on product pages.', $this->cgi, 2, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
                ('Enable Facebook-Open Graph markup', 'PLUGIN_SDATA_FOG_ENABLE', 'true', 'Show Facebook-Open Graph markup?<br>Shows Facebook og tags on all pages with additional product-specific tags on product pages.', $this->cgi, 3, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
                ('Enable Twitter Card markup', 'PLUGIN_SDATA_TWITTER_CARD_ENABLE', 'true', 'Show Twitter Card markup?<br>Shows on all pages.', $this->cgi, 4, 'zen_cfg_select_option(array(\'true\', \'false\'),'),

                ('Facebook Application ID', 'PLUGIN_SDATA_FOG_APPID', '', 'Enter your Facebook application ID (<a href=\"https://developers.facebook.com/docs/development/create-an-app\" target=\"_blank\">Get an application ID</a>).', $this->cgi, 5, NULL),
                ('Facebook Admin ID (optional)', 'PLUGIN_SDATA_FOG_ADMINID', '', 'Enter the Admin ID(s) of the Facebook user(s) that administer your Facebook fan page separated by commas. <a href=\"https://business.facebook.com\" target=\"_blank\">Facebook Business</a>.', $this->cgi, 6, null),
                ('Facebook Page (optional)', 'PLUGIN_SDATA_FOG_PAGE', '', 'Enter the full url/link to your facebook page e.g.: https://www.facebook.com/zencart/.', $this->cgi, 7, null),

                ('Logo (Schema)', 'PLUGIN_SDATA_LOGO', '', 'Enter the complete url to your logo image.', $this->cgi, 10, null),

                ('Street Address (Schema/OG)', 'PLUGIN_SDATA_STREET_ADDRESS', '', 'Enter the business street address.', $this->cgi, 11, null),
                ('City (Schema/OG)', 'PLUGIN_SDATA_LOCALITY', '', 'Enter the business town/city.', $this->cgi, 12, null),
                ('State (Schema/OG)', 'PLUGIN_SDATA_REGION', '', 'Enter the business state/province.', $this->cgi, 13, null),
                ('Postal Code (Schema/OG)', 'PLUGIN_SDATA_POSTALCODE', '', 'Enter the business postal code/zip', $this->cgi, 14, null),
                ('Country (Schema/OG)', 'PLUGIN_SDATA_COUNTRYNAME', '', 'Enter the business country name or <a href=\"https://en.wikipedia.org/wiki/ISO_3166-1\" target=\"_blank\">2 letter ISO code</a>', $this->cgi, 15, null),
                ('Email (Schema, optional)', 'PLUGIN_SDATA_EMAIL', '', 'Enter your Customer Service email address (lower case).', $this->cgi, 16, null),
                ('Telephone (Schema)', 'PLUGIN_SDATA_TELEPHONE', '', 'Enter the Customer Service phone number in international format eg.: +1-330-871-4357. The format (spaces/dashes) is not important.', $this->cgi, 17, null),
                ('Fax (Schema, optional)', 'PLUGIN_SDATA_FAX', '', 'Enter the Customer Service fax number in international format e.g. +1-877-453-1304). The format (spaces/dashes) is not important.', $this->cgi, 18, null),

                ('Available Languages (Schema, optional)', 'PLUGIN_SDATA_AVAILABLE_LANGUAGE', '', 'Languages spoken (for Schema contact point). Enter the language\'s name in English, separated by commas. If omitted, the language defaults to English.', $this->cgi, 19, null),

                ('Locales (OG)', 'PLUGIN_SDATA_FOG_LOCALES', '', 'Enter a comma-separated list of the database language_id and equivalent locale for each defined language e.g.: 1,en_GB,2,es_ES, etc. (no spaces).<br>Separate the parameters with commas.', $this->cgi, 22, null),

                ('Tax ID (Schema, optional)', 'PLUGIN_SDATA_TAXID', '', 'The Tax/Fiscal ID of the business (e.g. the TIN in the US or the CIF in Spain).', $this->cgi, 20, null),
                ('VAT Number (Schema, optional)', 'PLUGIN_SDATA_VATID', '', 'Value-added Tax ID of the business.', $this->cgi, 21, null),

                ('Profile/Social Pages (Schema-sameAs, optional)', 'PLUGIN_SDATA_SAMEAS', '', 'Enter a list of URLs to other (NOT Facebook, Twitter or Google Plus) profile or social pages related to your business (e.g. Instagram, TikTok, LinkedIn, Dun & Bradstreet, Yelp etc.).<br>Separate the URLs with commas.', $this->cgi, 22, null),

                ('Product Shipping Area (Schema, optional)', 'PLUGIN_SDATA_ELIGIBLE_REGION', '', 'Area to which you ship products.<br >Use the ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, or the GeoShape for the geo-political region(s).', $this->cgi, 23, null),

                ('Currency (Schema/OG)', 'PLUGIN_SDATA_PRICE_CURRENCY', '', 'Enter the currency code of the product price e.g.: EUR.', $this->cgi, 24, null),

                ('Product Delivery Time when in stock (Schema)', 'PLUGIN_SDATA_DELIVERYLEADTIME', '', 'Enter the average days from order to delivery when product is in stock (e.g.:2).', $this->cgi, 25, null),

                ('Product Delivery Time when out of stock (Schema)', 'PLUGIN_SDATA_DELIVERYLEADTIME_OOS', '', 'Enter the average days from order to delivery when product is out of stock (e.g.:7).', $this->cgi, 25, null),

                ('Product Condition (Schema/OG)', 'PLUGIN_SDATA_FOG_PRODUCT_CONDITION', 'new', 'Choose your product\'s condition.', $this->cgi, 27, 'zen_cfg_select_option(array(\'new\', \'used\', \'refurbished\'),'),

                ('Accepted Payment Methods (Schema)', 'PLUGIN_SDATA_ACCEPTED_PAYMENT_METHODS', 'ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, GoogleCheckout, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, JCB, MasterCard, VISA', 'List/delete as applicable the <a href=\"https://www.heppnetz.de/ontologies/goodrelations/v1#PaymentMethod\" target=\"_blank\">accepted payment methods</a>. e.g. ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, GoogleCheckout, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, JCB, MasterCard, VISA.', $this->cgi, 28, null),

                ('Legal Name (Schema, optional)', 'PLUGIN_SDATA_LEGAL_NAME', '', 'The registered company name.', $this->cgi, 29, null),

                ('Dun & Bradstreet DUNS number (Schema, optional)', 'PLUGIN_SDATA_DUNS', '', 'The Dun & Bradstreet DUNS number for identifying an organization or a business person.', $this->cgi, 30, null),

                ('Area Served (Schema-Customer Service, optional)', 'PLUGIN_SDATA_AREA_SERVED', '', 'The geographical region served (<a href=\"https://schema.org/areaServed\" target=\"_blank\">further details here</a>).<br>If omitted, the area is assumed to be global.)', $this->cgi, 31, null),

                ('Facebook Default Image: Product (optional)', 'PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE', '', 'Fallback image used in Facebook when there is no product image. Enter the full URL or leave blank to use the no-image file defined in the Admin->Images configuration.', $this->cgi, 35, null),
                ('Facebook Default Image: non Product (optional)', 'PLUGIN_SDATA_FOG_DEFAULT_IMAGE', '', 'Fallback image used in Facebook when there is no image on any page other than a product page. Enter the full URL or leave blank to use the logo file defined above.', $this->cgi, 36, null),
                ('Facebook Type - Non Product Page', 'PLUGIN_SDATA_FOG_TYPE_SITE', 'business.business', 'Enter an Open Graph type for your site - non-product pages (<a href=\"https://developers.facebook.com/docs/reference/opengraph/\" target=\"_blank\">Open Graph Types</a>)', $this->cgi, 37, null),
                ('Facebook Type - Product Page', 'PLUGIN_SDATA_FOG_TYPE_PRODUCT', 'product', 'Enter an Open Graph type for your site - product pages (<a href=\"https://developers.facebook.com/docs/reference/opengraph/\" target=\"_blank\">Open Graph Types</a>)', $this->cgi, 38, null),

                ('Twitter Default Image (optional)', 'PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE', '', 'Fallback image used in Twitter when there is no image defined. Enter the full URL.', $this->cgi, 39, null),
                ('Twitter Username', 'PLUGIN_SDATA_TWITTER_USERNAME', '', 'Enter your Twitter username (e.g.: @zencart).', $this->cgi, 40, null),
                ('Twitter Page URL', 'PLUGIN_SDATA_TWITTER_PAGE', '', 'Enter the full URL to your Twitter page (e.g.: https://twitter.com/zencart)', $this->cgi, 41, null),
                ('Google - Publisher URL', 'PLUGIN_SDATA_GOOGLE_PUBLISHER', '', 'Enter your Google Publisher URL/link (e.g. https://plus.google.com/+Pro-websNet/).', $this->cgi, 42, null),

                ('Google - Default Product Category', 'PLUGIN_SDATA_GOOGLE_PRODUCT_CATEGORY', '', 'Fallback/default Google product category ID (up to 6 digits).<br>Used when a product does not have a GPC defined as an custom product field (e.g. 5613 = Vehicles & Parts, Vehicle Parts & Accessories).<br><a href=\"https://support.google.com/merchants/answer/6324436?hl=en\">Google Product Taxonomy</a>', $this->cgi, 43, null),

                ('Reviews - Default Review Date', 'PLUGIN_SDATA_REVIEW_DEFAULT_DATE', '2020-09-23 13:48:39', 'In the case of a review having no date set (null), use this date.', $this->cgi, 44, null),
                ('No Review - Add One?', 'PLUGIN_SDATA_REVIEW_USE_DEFAULT', 'true', 'If a product has no reviews, use a default value/dummy review to prevent Google Tool warnings.', $this->cgi, 45, null),
                ('No Review - Average Rating', 'PLUGIN_SDATA_REVIEW_DEFAULT_VALUE', '4', 'Average rating for the default review (1-5).', $this->cgi, 46, null),

                ('Default Product Weight', 'PLUGIN_SDATA_DEFAULT_WEIGHT', '0.5', 'If product has no weight defined, use this value.', $this->cgi, 47, null),

                ('Out of Stock Status', 'PLUGIN_SDATA_OOS_DEFAULT', 'BackOrder', 'The default OOS status if a product is out of stock and has no custom field defined for OOS status.', $this->cgi, 48, 'zen_cfg_select_option(array(\'BackOrder\', \'Discontinued\', \'OutOfStock\', \'PreOrder\', \'PreSale\', \'SoldOut\'),'),
               ('Out of Stock - BackOrder/PreOrder Date', 'PLUGIN_SDATA_OOS_AVAILABILITY_DELAY', '10', 'The OOS BackOrder/PreSales conditions require an availability date.<br>Set the number of days to add to today\'s date, to create a new date.', $this->cgi, 49, null),

               ('Returns - Policy', 'PLUGIN_SDATA_RETURNS_POLICY', 'Finite', 'The type of return policy.', $this->cgi, 50, 'zen_cfg_select_option(array(\'Finite\', \'NotPermitted\', \'Unlimited\'),'),
               ('Returns - Days', 'PLUGIN_SDATA_RETURNS_DAYS', '14', 'In the case of the Finite return policy, the period (days limit) during which the product can be returned.', $this->cgi, 51, null),
               ('Returns - Methods', 'PLUGIN_SDATA_RETURNS_METHOD', 'Mail', 'In the case of the Finite/Unlimited return policies, the method of returning the product.', $this->cgi, 52, 'zen_cfg_select_option(array(\'Kiosk\', \'Mail\', \'Store\'),'),
               ('Returns - Fee', 'PLUGIN_SDATA_RETURNS_FEE', '0', 'The charge to the customer of returning the product.', $this->cgi, 53, null),
               ('Returns - Applicable Country', 'PLUGIN_SDATA_RETURNS_APPLICABLE_COUNTRY', '', 'The country in which the returns policy is applicable (2-char ISO e.g. ES).', $this->cgi, 54, null),
               ('Returns - Returns Country', 'PLUGIN_SDATA_RETURNS_POLICY_COUNTRY', '', 'The country to which the product must be returned (2-char ISO e.g. ES).', $this->cgi, 55, null),

               ('Limit - Product Name', 'PLUGIN_SDATA_MAX_NAME', '150', 'The maximum number of characters allowed in a product name.<br>Google permits up to 150.', $this->cgi, 56, null),
               ('Limit - Product Description', 'PLUGIN_SDATA_MAX_DESCRIPTION', '5000', 'The maximum number of characters allowed in a product description.<br>Google permits up to 5000.', $this->cgi, 57, null),

                ('Custom Product Field - Google Product Category', 'PLUGIN_SDATA_GPC_FIELD', 'products_google_product_category', 'The name of the custom field used in the <strong>products</strong> table for the Google Product Category.', $this->cgi, 58, null),
                ('Custom Product Field - GTIN', 'PLUGIN_SDATA_GTIN_FIELD', 'products_gtin', 'The name of the custom field used in the <strong>products</strong> table for the product-specific code GTIN (EAN, ISBN etc.).', $this->cgi, 59, null),

                ('Custom POS Field - GTIN', 'PLUGIN_SDATA_POS_GTIN_FIELD', 'pos_gtin', 'The name of the custom field used in the <strong>products_options_stock</strong> table for the product-specific GTIN code (EAN, ISBN etc.).', $this->cgi, 60, null),
                ('Custom POS Field - MPN', 'PLUGIN_SDATA_POS_MPN_FIELD', 'pos_mpn', 'The name of the custom field used in the <strong>products_options_stock</strong> table for the manufacturers product code.', $this->cgi, 61, null)
                ");

        if (!zen_page_key_exists($this->configPageKey)) {
            // -----
            // Register the plugin's configuration page for the admin menus.
            //
            zen_register_admin_page($this->configPageKey, 'BOX_CONFIGURATION_STRUCTURED_DATA', 'FILENAME_CONFIGURATION', "gID=$this->cgi", 'configuration', 'Y');
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function executeUninstall(): bool
    {
        zen_deregister_admin_pages($this->configPageKey);
        $this->deleteConfigurationGroup($this->configGroupTitle, true);
        return true;
    }

    /**
     * @return bool
     */
    protected function purgeOldFiles(): bool
    {
        $files_to_remove = [
            DIR_FS_ADMIN . 'includes/extra_datafiles/plugin_structured_data.php',
            DIR_FS_CATALOG . 'includes/templates/' . $this->template_dir . '/jscript/jscript_plugin_structured_data.php',
        ];

        $error = false;
        foreach ($files_to_remove as $key => $next_file) {
            if (file_exists($next_file)) {
                echo $next_file;
                $result = unlink($next_file);
                if (!$result && file_exists($next_file)) {
                    $error = true;
                    $this->errorContainer->addError(
                        0,
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, $next_file),
                        false,
                        // this str_replace has to do DIR_FS_ADMIN before CATALOG because catalog is contained within admin, so the results are wrong.
                        // also, '[admin_directory]' is used to obfuscate the admin dir name, in case the user copy/pastes output to a public forum for help.
                        sprintf(ERROR_UNABLE_TO_DELETE_FILE, str_replace([DIR_FS_ADMIN, DIR_FS_CATALOG], ['[admin_directory]/', ''], $next_file))
                    );
                }
            }
        }
        return !$error;
    }
}
