<?php
namespace Directorium;


$stringRenderer = array($settings, 'printStringField');

/**
 * Presentation configuration array.
 *
 * Format: [ key => [ default, label, validationCallback, renderingCallback ], ... ]
 */
return array(
	'enableDefaultStyles' =>  array(
		true,
		__('Enable default styles', 'directorium'),
		false,
		$stringRenderer
	),
	'enableThemeStyles' => array(
		false,
		__('Enable theme styles', 'directorium'),
		false,
		$stringRenderer
	)
);