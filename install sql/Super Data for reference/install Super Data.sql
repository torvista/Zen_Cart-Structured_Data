# FACEBOOK OPEN GRAPH      forked for super data
#
SELECT (@configuration_group_id:=configuration_group_id) 
FROM configuration_group 
WHERE configuration_group_title= 'Super Data' 
LIMIT 1;
DELETE FROM configuration WHERE configuration_group_id = @configuration_group_id AND @configuration_group_id != 0;
DELETE FROM configuration_group WHERE configuration_group_id = @configuration_group_id AND @configuration_group_id != 0;

INSERT INTO configuration_group (configuration_group_id, configuration_group_title, configuration_group_description, sort_order, visible) VALUES (NULL, 'Super Data', 'Set Super Data Options', '1', '1');
SET @configuration_group_id=last_insert_id();
UPDATE configuration_group SET sort_order = @configuration_group_id WHERE configuration_group_id = @configuration_group_id;

INSERT INTO configuration (configuration_id, configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) VALUES 
(NULL, 'Enable Module', 'FACEBOOK_OPEN_GRAPH_STATUS', 'true', 'Enable Super Data?', @configuration_group_id, 30, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
(NULL, 'Application ID', 'FACEBOOK_OPEN_GRAPH_APPID', '', 'Please enter your application ID (<a href="http://developers.facebook.com/setup/" target="_blank">Get an application ID</a>)', @configuration_group_id, 31, NOW(), NULL, NULL),
(NULL, 'Application Secret', 'FACEBOOK_OPEN_GRAPH_APPSECRET', '', 'Please enter your application secret', @configuration_group_id, 32, NOW(), NULL, NULL),
(NULL, 'Admin ID', 'FACEBOOK_OPEN_GRAPH_ADMINID', '', 'Enter the Admin ID(s) of the Facebook user(s) that administer your Facebook fan page separated by commas (<a href="http://www.facebook.com/insights/" target="_blank">Insights for your domain</a>)', @configuration_group_id, 33, NOW(), NULL, NULL),
(NULL, 'Default Image', 'FACEBOOK_OPEN_GRAPH_DEFAULT_IMAGE', '', 'Enter the full path to your default image or leave blank to disable.  The default image is only used when the product image cannot be found.', @configuration_group_id, 34, NOW(), NULL, NULL),
(NULL, 'Type', 'FACEBOOK_OPEN_GRAPH_TYPE', 'website', 'Enter an Open Graph type for your products (<a href="http://developers.facebook.com/docs/opengraph#types" target="_blank">Open Graph Types</a>)', @configuration_group_id, 35, NOW(), NULL, NULL),
(NULL, 'Use cPath', 'FACEBOOK_OPEN_GRAPH_CPATH', 'true', 'Include the cPath in your URLs?', @configuration_group_id, 36, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
(NULL, 'Include Language', 'FACEBOOK_OPEN_GRAPH_LANGUAGE', 'false', 'Include the language in your URLs?', @configuration_group_id, 37, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
(NULL, 'Use Canonical URL', 'FACEBOOK_OPEN_GRAPH_CANONICAL', 'true', 'Use the canonical URL from ZC 1.3.9 or try and recreate the URL?', @configuration_group_id, 38, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),


(NULL, 'Google Publisher', 'FACEBOOK_OPEN_GRAPH_GOOGLE_PUBLISHER', '', 'Please enter your full Google Publisher url/link (https://plus.google.com/+Pro-websNet/)', @configuration_group_id, 39, NOW(), NULL, NULL),
(NULL, 'Your Logo', 'FACEBOOK_OPEN_GRAPH_LOGO', '', 'Please enter your full link to your logo url/link https:// is better!', @configuration_group_id, 40, NOW(), NULL, NULL),
(NULL, 'Street Address', 'FACEBOOK_OPEN_GRAPH_STREET_ADDRESS', '', 'Please enter your street address', @configuration_group_id, 41, NOW(), NULL, NULL),
(NULL, 'City', 'FACEBOOK_OPEN_GRAPH_CITY', '', 'Please enter your city', @configuration_group_id, 42, NOW(), NULL, NULL),
(NULL, 'State', 'FACEBOOK_OPEN_GRAPH_STATE', '', 'Please enter your state', @configuration_group_id, 43, NOW(), NULL, NULL),
(NULL, 'Postal Code', 'FACEBOOK_OPEN_GRAPH_ZIP', '', 'Please enter your postal code/zip', @configuration_group_id, 44, NOW(), NULL, NULL),
(NULL, 'Country', 'FACEBOOK_OPEN_GRAPH_COUNTRY', '', 'Please enter your 2 letter country code such as US', @configuration_group_id, 45, NOW(), NULL, NULL),
(NULL, 'Email', 'FACEBOOK_OPEN_GRAPH_EMAIL', '', 'Please enter your customer service email address (all lower case!)', @configuration_group_id, 46, NOW(), NULL, NULL),
(NULL, 'Phone', 'FACEBOOK_OPEN_GRAPH_PHONE', '', 'Required. An internationalized version of the phone number, starting with the “+” symbol and country code (+1 in the US and Canada). Like this +1-330-871-4357', @configuration_group_id, 47, NOW(), NULL, NULL),
(NULL, 'Twitter Handle', 'FACEBOOK_OPEN_GRAPH_TWUSER', '', 'Please enter your Twitter Handle like this @prowebs', @configuration_group_id, 48, NOW(), NULL, NULL),
(NULL, 'Facebook Page', 'FACEBOOK_OPEN_GRAPH_FBPG', '', 'Please enter your full url/link to your facebook page (https://www.facebook.com/prowebs)', @configuration_group_id, 49, NOW(), NULL, NULL),
(NULL, 'Locale', 'FACEBOOK_OPEN_GRAPH_LOCALE', '', 'Optional details about the language spoken. Languages may be specified by their common English name. If omitted, the language defaults to English.', @configuration_group_id, 50, NOW(), NULL, NULL),
(NULL, 'Currency', 'FACEBOOK_OPEN_GRAPH_CUR', '', 'Please enter your currency code such as USD', @configuration_group_id, 51, NOW(), NULL, NULL),
(NULL, 'Lead Time', 'FACEBOOK_OPEN_GRAPH_DTS', '', 'Please enter the average days until you ship orders such as 2', @configuration_group_id, 52, NOW(), NULL, NULL),
(NULL, 'Condition', 'FACEBOOK_OPEN_GRAPH_COND', '', 'Please enter your products condition (NewCondition, UsedCondition, RefurbishedCondition, DamagedCondition)', @configuration_group_id, 53, NOW(), NULL, NULL),
(NULL, 'Payment Type 1', 'FACEBOOK_OPEN_GRAPH_PAY1', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 54, NOW(), NULL, NULL),
(NULL, 'Payment Type 2', 'FACEBOOK_OPEN_GRAPH_PAY2', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 55, NOW(), NULL, NULL),
(NULL, 'Payment Type 3', 'FACEBOOK_OPEN_GRAPH_PAY3', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 56, NOW(), NULL, NULL),
(NULL, 'Payment Type 4', 'FACEBOOK_OPEN_GRAPH_PAY4', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 57, NOW(), NULL, NULL),
(NULL, 'Payment Type 5', 'FACEBOOK_OPEN_GRAPH_PAY5', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 58, NOW(), NULL, NULL),
(NULL, 'Payment Type 6', 'FACEBOOK_OPEN_GRAPH_PAY6', '', 'Please enter ONE of the following payment types EXACTLY (ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, MasterCard, VISA, JCB, GoogleCheckout)', @configuration_group_id, 59, NOW(), NULL, NULL),
(NULL, 'Tax ID', 'FACEBOOK_OPEN_GRAPH_TID', '', 'The Tax / Fiscal ID of the organization (e.g. the TIN in the US or the CIF/NIF in Spain))', @configuration_group_id, 60, NOW(), NULL, NULL),
(NULL, 'DUNS', 'FACEBOOK_OPEN_GRAPH_DUNS', '', 'The Dun & Bradstreet DUNS number for identifying an organization or business person.', @configuration_group_id, 61, NOW(), NULL, NULL),
(NULL, 'Fax', 'FACEBOOK_OPEN_GRAPH_FAX', '', 'Please enter your fax number like this +1-877-453-1304.', @configuration_group_id, 62, NOW(), NULL, NULL),
(NULL, 'VAT ID', 'FACEBOOK_OPEN_GRAPH_VAT', '', 'Value-added Tax ID of your organization.)', @configuration_group_id, 63, NOW(), NULL, NULL),
(NULL, 'Legal Name', 'FACEBOOK_OPEN_GRAPH_LEG', '', 'The official name of the organization, e.g. the registered company name.)', @configuration_group_id, 64, NOW(), NULL, NULL),
(NULL, 'Area Served', 'FACEBOOK_OPEN_GRAPH_AREA', '', 'Optional. The geographical region served by the number, specified as a Schema.org/AdministrativeArea. Countries may be specified concisely using just their standard ISO-3166 two-letter code, as in the examples at right. If omitted, the number is assumed to be global..)', @configuration_group_id, 65, NOW(), NULL, NULL),
(NULL, 'Twitter Page', 'FACEBOOK_OPEN_GRAPH_TWIT', '', 'Please enter your full url/link to your twitter page (https://twitter.com/prowebs)', @configuration_group_id, 66, NOW(), NULL, NULL),
(NULL, 'Linkedin Page', 'FACEBOOK_OPEN_GRAPH_LINK', '', 'Please enter your full url/link to your Linkedin page (http://www.linkedin.com/company/pro-web-inc-/)', @configuration_group_id, 67, NOW(), NULL, NULL),
(NULL, 'Another Profile Page', 'FACEBOOK_OPEN_GRAPH_PROF1', '', 'Please enter your full url/link to your profile page (https://www.dandb.com/businessdirectory/prowebsinc-woodbine-ga-37349028.html)', @configuration_group_id, 68, NOW(), NULL, NULL),
(NULL, 'Another Profile Page', 'FACEBOOK_OPEN_GRAPH_PROF2', '', 'Please enter your full url/link to your profile page (http://www.yelp.com/biz/pro-webs-woodbine)', @configuration_group_id, 69, NOW(), NULL, NULL),
(NULL, 'Shipping to', 'FACEBOOK_OPEN_GRAPH_ELER', '', 'The ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, or the GeoShape for the geo-political region(s) for which the offer or delivery charge specification is valid. Such as US', @configuration_group_id, 70, NOW(), NULL, NULL);
# Register the configuration page for Admin Access Control
INSERT IGNORE INTO admin_pages (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES ('configSuperData','BOX_CONFIGURATION_SUPERDATA','FILENAME_CONFIGURATION',CONCAT('gID=',@configuration_group_id),'configuration','Y',@configuration_group_id);
