<?php
namespace Directorium;


$stringRenderer = array($settings, 'printStringField');

/**
 * General configuration array.
 *
 * Format: [ key => [ default, validationCallback, renderingCallback ], ... ]
 */
return array(
	'directorySlug' =>  array(
		'directory',
		__('Directory slug', 'directorium'),
		false,
		$stringRenderer
	),
	'geographySlug' => array(
		'geography',
		__('Geography slug', 'directorium'),
		false,
		$stringRenderer
	),
	'btypesSlug' => array(
		'business-types',
		__('Business types slug', 'directorium'),
		false,
		$stringRenderer
	),
	'listPage' => array(
		'directory/my-listings',
		__('Owner&#146;s admin list', 'directorium'),
		false,
		$stringRenderer
	),
	'editorPage' => array(
		'directory/edit',
		__('Listing editor page', 'directorium'),
		false,
		$stringRenderer
	)
);