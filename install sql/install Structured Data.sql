# Structured Data - Install

#Install new constants
INSERT INTO configuration_group (configuration_group_title, configuration_group_description, sort_order, visible) VALUES 
('Structured Data', 'Set Structured Data Options', '1', '1');

SET @configuration_group_id=last_insert_id();
UPDATE configuration_group SET sort_order = @configuration_group_id WHERE configuration_group_id = @configuration_group_id;

INSERT INTO configuration (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) VALUES 

('Enable Structured Data generation', 'PLUGIN_SDATA_ENABLE', 'false', 'Enable Structured Data processing code and display of markup groups? This is a global option.', @configuration_group_id, 1, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),

('Enable Schema markup', 'PLUGIN_SDATA_SCHEMA_ENABLE', 'false', 'Show Schema markup?<br />Shows JSON-LD blocks for Organisation and Breadcrumbs on all pages, Product on product pages.', @configuration_group_id, 2, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Enable Facebook-Open Graph markup', 'PLUGIN_SDATA_FOG_ENABLE', 'false', 'Show Facebook-Open Graph markup?<br />Shows Facebook og tags on all pages with additional product-specific tags on product pages.', @configuration_group_id, 3, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Enable Twitter Card markup', 'PLUGIN_SDATA_TWITTER_CARD_ENABLE', 'false', 'Show Twitter Card markup?<br />Shows on all pages.', @configuration_group_id, 4, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Facebook Application ID', 'PLUGIN_SDATA_FOG_APPID', '', 'Enter your Facebook application ID (<a href="http://developers.facebook.com/setup/" target="_blank">Get an application ID</a>).', @configuration_group_id, 5, NOW(), NULL, NULL),
('Facebook Admin ID (optional)', 'PLUGIN_SDATA_FOG_ADMINID', '', 'Enter the Admin ID(s) of the Facebook user(s) that administer your Facebook fan page separated by commas. <a href="http://www.facebook.com/insights/" target="_blank">Insights for your domain</a>.', @configuration_group_id, 6, NOW(), NULL, NULL),
('Facebook Page (optional)', 'PLUGIN_SDATA_FOG_PAGE', '', 'Enter the full url/link to your facebook page eg.:https://www.facebook.com/zencart/.', @configuration_group_id, 7, NOW(), NULL, NULL),

('Logo (Schema)', 'PLUGIN_SDATA_LOGO', '', 'Enter the full url to your logo image.', @configuration_group_id, 10, NOW(), NULL, NULL),
('Street Address (Schema/OG)', 'PLUGIN_SDATA_STREET_ADDRESS', '', 'Enter the business street address.', @configuration_group_id, 11, NOW(), NULL, NULL),
('City (Schema/OG)', 'PLUGIN_SDATA_LOCALITY', '', 'Enter the business town/city.', @configuration_group_id, 12, NOW(), NULL, NULL),
('State (Schema/OG)', 'PLUGIN_SDATA_REGION', '', 'Enter the business state/province.', @configuration_group_id, 13, NOW(), NULL, NULL),
('Postal Code (Schema/OG)', 'PLUGIN_SDATA_POSTALCODE', '', 'Enter the business postal code/zip', @configuration_group_id, 14, NOW(), NULL, NULL),
('Country (Schema/OG)', 'PLUGIN_SDATA_COUNTRYNAME', '', 'Enter the business country name or <a href="https://en.wikipedia.org/wiki/ISO_3166-1" target="_blank">2 letter ISO code</a>', @configuration_group_id, 15, NOW(), NULL, NULL),
('Email (Schema, optional)', 'PLUGIN_SDATA_EMAIL', '', 'Enter your customer service email address (lower case).', @configuration_group_id, 16, NOW(), NULL, NULL),
('Phone (Schema)', 'PLUGIN_SDATA_TELEPHONE', '', 'Enter the customer service phone number in international format eg.: +1-330-871-4357. Format (spaces/dashes) is not important.', @configuration_group_id, 17, NOW(), NULL, NULL),
('Fax (Schema, optional)', 'PLUGIN_SDATA_FAX', '', 'Enter the customer service fax number in international format eg.: +1-877-453-1304).', @configuration_group_id, 18, NOW(), NULL, NULL),
('Available Languages (Schema, optional)', 'PLUGIN_SDATA_AVAILABLE_LANGUAGE', '', 'Languages spoken (for Schema contact point). Enter the language\'s english name, separated by commas. If omitted, the language defaults to English.', @configuration_group_id, 19, NOW(), NULL, NULL),
('Locales (OG)', 'PLUGIN_SDATA_FOG_LOCALES', '', 'Enter a comma-separated list of the database language_id and equivalent locale for each defined language eg.: 1,en_GB,2,es_ES, etc. (no spaces).<br />Separate the urls with commas.', @configuration_group_id, 22, NOW(), NULL, NULL),
('Tax ID (Schema, optional)', 'PLUGIN_SDATA_TAXID', '', 'The Tax/Fiscal ID of the business (eg. the TIN in the US or the CIF/NIF in Spain).', @configuration_group_id, 20, NOW(), NULL, NULL),
('VAT Number (Schema, optional)', 'PLUGIN_SDATA_VATID', '', 'Value-added Tax ID of the business.', @configuration_group_id, 21, NOW(), NULL, NULL),
('Profile/Social Pages (Schema-sameAs, optional)', 'PLUGIN_SDATA_SAMEAS', '', 'Enter a list of urls to other (NOT Facebook, Twitter or Google Plus) profile or social pages related to your business (eg. Linked In, Dun & Bradstreet, Yelp etc.).<br />Separate the urls with commas.', @configuration_group_id, 22, NOW(), NULL, NULL),
('Product Shipping Area (Schema, optional)', 'PLUGIN_SDATA_ELIGIBLE_REGION', '', 'Area to which you ship products.<br >Use the ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, or the GeoShape for the geo-political region(s).', @configuration_group_id, 23, NOW(), NULL, NULL),
('Currency (Schema/OG)', 'PLUGIN_SDATA_PRICE_CURRRENCY', '', 'Enter the currency code of the product price eg.: EUR.', @configuration_group_id, 24, NOW(), NULL, NULL),
('Product Delivery Time when in stock (Schema)', 'PLUGIN_SDATA_DELIVERYLEADTIME', '', 'Enter the average days from order to delivery when product is in stock (eg.:2).', @configuration_group_id, 25, NOW(), NULL, NULL),
('Product Delivery Time when out of stock (Schema)', 'PLUGIN_SDATA_DELIVERYLEADTIME_OOS', '', 'Enter the average days from order to delivery when product is out of stock (eg.:7).', @configuration_group_id, 25, NOW(), NULL, NULL),

('Product Condition (Schema/OG)', 'PLUGIN_SDATA_FOG_PRODUCT_CONDITION', 'new', 'Choose your product\'s condition.', @configuration_group_id, 27, NOW(), NULL, 'zen_cfg_select_option(array(\'new\', \'used\', \'refurbished\'),'),
('Accepted Payment Methods (Schema)', 'PLUGIN_SDATA_ACCEPTED_PAYMENT_METHODS', 'ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, GoogleCheckout, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, JCB, MasterCard, VISA', 'List/delete as aplicable the <a href="http://www.heppnetz.de/ontologies/goodrelations/v1#PaymentMethod" target="_blank">accepted payment methods</a>. eg. ByBankTransferInAdvance, ByInvoice, Cash, CheckInAdvance, COD, DirectDebit, GoogleCheckout, PayPal, PaySwarm, AmericanExpress, DinersClub, Discover, JCB, MasterCard, VISA.', @configuration_group_id, 28, NOW(), NULL, NULL),
('Legal Name (Schema, optional)', 'PLUGIN_SDATA_LEGAL_NAME', '', 'The registered company name.', @configuration_group_id, 29, NOW(), NULL, NULL),
('Dun & Bradstreet DUNS number (Schema, optional)', 'PLUGIN_SDATA_DUNS', '', 'The Dun & Bradstreet DUNS number for identifying an organization or business person.', @configuration_group_id, 30, NOW(), NULL, NULL),
('Area Served (Schema-Customer Service, optional)', 'PLUGIN_SDATA_AREA_SERVED', '', 'The geographical region served (<a href="https://schema.org/areaServed" target="_blank">further details here</a>).<br />If omitted, the area is assumed to be global.)', @configuration_group_id, 31, NOW(), NULL, NULL),

('Facebook Default Image: Product (optional)', 'PLUGIN_SDATA_FOG_DEFAULT_PRODUCT_IMAGE', '', 'Fallback image used in Facebook when there is no product image. Enter the full url or leave blank to use the no-image file defined in the Admin->Images configuration.', @configuration_group_id, 35, NOW(), NULL, NULL),
('Facebook Default Image: non Product (optional)', 'PLUGIN_SDATA_FOG_DEFAULT_IMAGE', '', 'Fallback image used in Facebook when there is no image on any page other than a product page. Enter the full url or leave blank to use the logo file defined above.', @configuration_group_id, 36, NOW(), NULL, NULL),
('Facebook Type - Non Product Page', 'PLUGIN_SDATA_FOG_TYPE_SITE', 'business.business', 'Enter an Open Graph type for your site - non-product pages (<a href="https://developers.facebook.com/docs/reference/opengraph/" target="_blank">Open Graph Types</a>)', @configuration_group_id, 37, NOW(), NULL, NULL),
('Facebook Type - Product Page', 'PLUGIN_SDATA_FOG_TYPE_PRODUCT', 'product', 'Enter an Open Graph type for your site - product pages (<a href="https://developers.facebook.com/docs/reference/opengraph/" target="_blank">Open Graph Types</a>)', @configuration_group_id, 38, NOW(), NULL, NULL),
('Twitter Default Image (optional)', 'PLUGIN_SDATA_TWITTER_DEFAULT_IMAGE', '', 'Fallback image used in Twitter when there is no image defined. Enter the full url.', @configuration_group_id, 39, NOW(), NULL, NULL),
('Twitter Username', 'PLUGIN_SDATA_TWITTER_USERNAME', '', 'Enter your Twitter username (eg.: @zencart).', @configuration_group_id, 40, NOW(), NULL, NULL),
('Twitter Page URL', 'PLUGIN_SDATA_TWITTER_PAGE', '', 'Enter the full url to your Twitter page (eg.: https://twitter.com/zencart)', @configuration_group_id, 41, NOW(), NULL, NULL),

('Google Publisher', 'PLUGIN_SDATA_GOOGLE_PUBLISHER', '', 'Enter your Google Publisher url/link (eg.: https://plus.google.com/+Pro-websNet/). Link does not display if field empty.', @configuration_group_id, 42, NOW(), NULL, NULL);

# Register the configuration page for Admin Access Control
INSERT IGNORE INTO admin_pages (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES ('configStructuredData','BOX_CONFIGURATION_STRUCTURED_DATA','FILENAME_CONFIGURATION',CONCAT('gID=',@configuration_group_id),'configuration','Y',@configuration_group_id);
