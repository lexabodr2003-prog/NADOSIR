<?php

/*
 *  init $getProductId Method:
 * 	getProductIdBySku		- by Product SKU
 * 	getProductIdByModel		- by Product Model
 * 	getProductIdByName		- by Product Name
 * 	getProductIdById		- by Product ID
 * 
 * 
 *  DataFormat:
 * 	{parent_id}|{image}|{sort}|{type_id}
 * 	{parent_id}|{image}|{sort}
 * 	{parent_id}|{sort}
 * 	{parent_id}|{image}
 * 	{parent_id}
 * 
 * 	 - Do not change a delimiter char "|" in DataFormat
 * 
 * FieldTypeFormat:	Default 1
 * 	value: 1	- import and export single field as _HPMODELS_LINKS_
 * 	value: 2	- import and export multiple fields
 *
 * CSV fields name:
 * 	{parent_id}	- _HPM_PARENT_ID_
 * 	{image}		- _HPM_IMAGE_
 * 	{sort}		- _HPM_SORT_ORDER_
 * 	{type_id}	- _HPM_TYPE_ID_
 * 
 * 	- Only use in FieldTypeFormat = 2
 */

$_['csvprcie_pro_plugin_hpmodel_links'] = array(
	'getProductId' => 'getProductIdById',
	'FieldDelimiter' => '|',
	'StringDelimiter' => "\n",
	'DefaultTypeId' => 0,
	'DefaultSortOrder' => 1,
	'FieldTypeFormat' => 1,
	'DataFormat' => '{parent_id}|{image}|{sort}|{type_id}',
);
