<?php
namespace Directorium;

$sectionRenderer = array($settings, 'printSection');

/**
 * Options configuration array - controls which settings appear on the
 * settings admin page.
 *
 * Format: [ key => [ default, label, validationCallback, renderingCallback ], ... ]
 */
return array(
	'directoryPages' =>  array(
		array(
			'general.directorySlug',
			'general.geographySlug',
			'general.btypesSlug',
			'general.listPage',
			'general.editorPage'
		),
		__('Directory Pages', 'directorium'),
		false,
		$sectionRenderer
	),
	'presentation' =>  array(
		array(
			'presentation.enableDefaultStyles',
			'presentation.enableThemeStyles'
		),
		__('Presentation', 'directorium'),
		false,
		$sectionRenderer
	),
	'listingeditor' =>  array(
		array(
			'general.disablePostRevisions'
		),
		__('Editor Settings', 'directorium'),
		false,
		$sectionRenderer
	)
);