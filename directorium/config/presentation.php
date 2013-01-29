<?php
namespace Directorium;


$checkboxRenderer = array($settings, 'printCheckboxField');

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
		$checkboxRenderer
	),
	'enableThemeStyles' => array(
		false,
		__('Enable theme styles', 'directorium'),
		false,
		$checkboxRenderer
	)
);