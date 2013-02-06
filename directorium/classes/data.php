<?php
namespace Directorium;


class Data {
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


	/**
	 * Takes serialized data and unserializes it. If the data is not serialized it returns
	 * it in its original state.
	 *
	 * @param $data
	 * @return mixed
	 */
	public static function makeUnserialized($data) {
		// Assume it *is* serialized initially
		$isSerialized = true;

		// Look for indicators
		foreach (array('{', '}', ':') as $expectedChar)
			if (strpos($data, $expectedChar) === false) $isSerialized = false;

		// If we still think it is serialized lets try unserializing
		if ($isSerialized) {
			$object = @unserialize($data);
			if ($object !== false and $data !== 'b:0;') $data = $object;
		}

		return $data;
	}
}