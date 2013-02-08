<?php
namespace Directorium;


class HTML {
	/**
	 * Wraps $text (which can be a single string or an array of strings) in a div,
	 * with the classes and IDs specified being added to the wrapping div.
	 *
	 * @param mixed array|string $text
	 * @param string $class
	 * @param string $id
	 * @internal param string $divClass
	 * @internal param string $divID
	 * @return mixed string|array
	 */
	public static function wrapInDiv($text, $class = '', $id = '') {
		if (!is_array($text)) {
			$nonArray = true;
			$text = (array) $text;
		}

		foreach ($text as &$item) {
			$classAttr = $idAttr = '';
			if (!empty($class)) $classAttr = ' class="'.$class.'" ';
			if (!empty($id)) $idAttr = ' id="'.$id.'" ';
			$item = "<div{$classAttr}{$idAttr}>$item</div>";
		}

		if (isset($nonArray)) return $text[0];
		else return $text;
	}
}