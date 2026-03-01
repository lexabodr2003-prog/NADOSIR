<?php

// Heading
$_['heading_title'] = 'General';
$_['heading_title_normal'] = 'CSV Price Pro import/export OC3';

// Text
$_['text_module'] = 'Modules';
$_['text_extension'] = 'Extensions';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_auto_ajax'] = 'Automatically AJAX';
$_['text_auto'] = 'Automatically';
$_['text_mirror'] = 'Mirror';
$_['text_manual'] = 'Manual';
$_['text_success'] = 'Success: You have modified CSV Price Pro import/export general settings!';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_product_backup'] = 'Products';
$_['text_category_backup'] = 'Categories';
$_['text_manufacturer_backup'] = 'Manufacturers';
$_['text_product_all_backup'] = 'Products, Categories, Manufacturers';
$_['text_full_backup'] = 'Full Backup';
$_['text_opencart_backup'] = 'OpenCart';
$_['text_raw_backup'] = 'MySQL Dump (DELETE TABLE, CREATE TABLE, INSERT)';
$_['text_core_update'] = 'Update core settings';
$_['text_before_creating_indexes'] = 'Before creating indexes, enable your OpenCart Maintenance Mode in the admin section Settings -> Stores list -> Edit your store';

// Tabs
$_['tab_setting'] = 'General Settings';
$_['tab_tool_image'] = 'Tool Images';
$_['tab_tool_backup'] = 'Tool Backup';
$_['tab_tool_database'] = 'Database optimization';
$_['button_create_index'] = 'Create all the missing indexes';

// Butons
$_['button_save'] = 'Save';
$_['button_export'] = 'Export';
$_['button_delete_image_cache'] = 'Clear image cache table';

// Entry
$_['entry_csv_import_mod'] = 'Import data mode';
$_['entry_each_iteration_timeout'] = 'Each iteration timeout AJAX';
$_['entry_image_download_mod'] = 'Image download mode';
$_['entry_save_img_table'] = 'Save Image Table';
$_['entry_work_directory'] = 'Working directory';
$_['entry_product_log'] = 'Product Import Log';
$_['entry_clear_image_cache'] = 'Remove images cache';
$_['entry_backup_data'] = 'Backup data';
$_['entry_backup_type'] = 'Backup type';
$_['entry_backup_zip'] = 'Using zipfile';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify CSV Price Pro import/export!';

// Helper
$_['prop_descr'] = ' 
prop_descr[0]="<p><b>Image download mode</b><br />Mirror mode - Download files and create same file structure as the source.</p>";
prop_descr[1]="<p><b>Image download mode</b></p><p><i>Auto</i> - Saving a file and automatically create directories with random names.</p><p><i>Mirror mode</i> - Download files and create same file structure as the source</p>";
prop_descr[2]="<p><b>Save Image Table</b></p><p>Do not delete the SQL table oc_csvprice_pro_images if the module is deleted.</p>";
prop_descr[3]="<p><b>Working directory</b></p><p>This directory must exist and be writable.</p>";
prop_descr[4]="<p>After installing an extension supported by CSV Price Pro import/export, you need to update the core settings.</p>";
prop_descr[5]="<p>We recommend you to tune Each iteration timeout option to 60-180 seconds, to allow the last each iteration to complete and gracefully shutdown.</p><p>This option must be less than the set max_execution_time in PHP setting.</p>";
';
