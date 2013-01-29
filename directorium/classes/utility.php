<?php
namespace Directorium;


class Utility {
	/**
	 * Takes a comma separated list of IDs and returns them as an array. Each is
	 * expected to be an integer value.
	 *
	 * @param $string
	 * @return array
	 */
	public static function parseCSVidList($string) {
		$elements = self::parseCSV($string);

		foreach ($elements as &$item)
			$item = absint($item);

		return $elements;
	}


	/**
	 * Returns an array of values from a string of comma separated values. By default
	 * leading/trailing whitespace is trimmed from each value unless $trim = false.
	 *
	 * @param $string
	 * @param bool $trim
	 * @return array
	 */
	public static function parseCSV($string, $trim = true) {
		$elements = explode(',', $string);

		if ($trim) foreach ($elements as &$item)
			$item = trim($item);

		return $elements;
	}
}