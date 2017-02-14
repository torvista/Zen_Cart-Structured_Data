# Structured Data - Uninstall

#delete existing constants
SELECT (@configuration_group_id:=configuration_group_id) 
FROM configuration_group 
WHERE configuration_group_title= 'Structured Data' 
LIMIT 1;
DELETE FROM configuration WHERE configuration_group_id = @configuration_group_id AND @configuration_group_id != 0;
DELETE FROM configuration_group WHERE configuration_group_id = @configuration_group_id AND @configuration_group_id != 0;

#delete admin page
DELETE FROM admin_pages WHERE page_key = 'configStructuredData';