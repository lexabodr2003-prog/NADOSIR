<?php

// Heading
$_['heading_title'] = 'Task Scheduler';
$_['heading_title_normal'] = 'CSV Price Pro import/export';

// Text global
$_['text_module'] = 'Modules';
$_['text_extension'] = 'Extensions';
$_['text_default'] = ' <b>(Default)</b>';
$_['text_yes'] = 'Yes';
$_['text_no'] = 'No';
$_['text_enabled'] = 'Enabled';
$_['text_disabled'] = 'Disabled';
$_['text_select_all'] = 'Select All';
$_['text_unselect_all'] = 'Unselect All';
$_['text_select'] = 'Select';
$_['text_show_all'] = 'Show all';
$_['text_hide_all'] = 'Hide unchecked';
$_['text_all'] = 'All';
$_['text_no_results'] = 'No results!';
$_['text_none'] = ' --- None --- ';
$_['text_edit'] = 'Edit';
$_['text_delete'] = 'Delete';
$_['text_as'] = 'As %s';
$_['text_confirm_delete'] = 'Deleting cannot be cancelled! Are you sure that you want to do it?';
$_['text_success'] = 'Settings has been updated successfully!';

// Text
$_['text_hours'] = 'Hours';
$_['text_minutes'] = 'Minutes';
$_['text_import'] = 'Import';
$_['text_import_product'] = 'Product import';
$_['text_import_category'] = 'Category import';
$_['text_import_manufacturer'] = 'Category import';
$_['text_import_order'] = 'Order import';
$_['text_export'] = 'Export';
$_['text_export_product'] = 'Product export';
$_['text_export_category'] = 'Category export';
$_['text_export_manufacturer'] = 'Manufacturer export';
$_['text_export_order'] = 'Order export';

$_['cron_description'] = '
<p><strong>Cron (Command Run ON)</strong> — A cron job gives you the ability to automate certain commands or scripts on their site.</p>
<p>Most scripts that require the use of a cron job will give you specific instructions on what needs to be setup, frequently giving examples from your hosting.
For instructions and additional information, contact the technical support of your hosting. </p>
<p>&nbsp;<br>The command to execute the PHP-script to run the extension <strong>CSV Price Pro import/export</strong>:</p>
<pre>php -d max_execution_time=0 %s -k 1619759987 > /dev/null 2>&1</pre>
<p>Option <strong>-d max_execution_time=0</strong>  it is not to limit the execution of the PHP script by time.</p>
<p>Option <strong>-k</strong> and a number <strong>(Key)</strong> indicates which task to run, the list of available commands can be viewed by click on <button disabled type="button" class="btn btn-info btn-sm"><i class="fa fa-eye"></i></button> in the task list.</p>
<p>You will find exactly how the command for executing the PHP script should be specified in the instructions for setting up the corresponding section in the control panel of your hosting account or by checking with the technical support service of your hosting.</p>
';


// Butons
$_['button_save'] = 'Save';
$_['button_cancel'] = 'Cancel';
$_['button_delete'] = 'Delete';
$_['button_add'] = 'Add';
$_['button_view'] = 'View';
$_['button_close'] = 'Close';
$_['button_edit'] = 'Edit';
$_['button_add'] = 'Add';
$_['button_show_description'] = 'Show description';
$_['button_hide_description'] = 'Hide description';

// Column
$_['column_job_id'] = 'id';
$_['column_job_type'] = 'Type';
$_['column_profile_name'] = 'Profile';
$_['column_job_key'] = 'Key';
$_['column_job_file_location'] = 'Source/Destination';
$_['column_job_time_start'] = 'Start time';
$_['column_job_file'] = 'File name';
$_['column_status'] = 'Status';
$_['column_action'] = 'Action';

// Entry
$_['entry_job_id'] = 'id';
$_['entry_job_type'] = 'Type';
$_['entry_profile'] = 'Profile';
$_['entry_file_location'] = 'Source/Destination';
$_['entry_time_start'] = 'Start time';
$_['entry_ftp_host'] = 'FTP host';
$_['entry_ftp_user'] = 'FTP user';
$_['entry_ftp_passwd'] = 'FTP password';
$_['entry_file_path'] = 'The path to the file/URL';
$_['entry_status'] = 'Status';
$_['entry_job_offline'] = 'Starting on time';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify CSV Price Pro import/export!';
$_['error_import_field_caption'] = 'Incorrect CSV header';

// Prop Description
$_['prop_descr'] = '
prop_descr[0]="<p><b>FTP host</b></p><p>The name of the hosting server, the IP address of the hosting server or domain <br> Through a colon, you can specify the FTP port (not required)<br><br> Example with FTP port: <b>exmple.com:21</b></p>";
';
