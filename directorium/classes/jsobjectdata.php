<?php
namespace Directorium;


class JSObjectData {
	/**
	 * An array of (potentially translated) strings for use in JS code, in:
	 * [identifier => string, ...] format.
	 *
	 * @var array
	 */
	protected static $data = array();

	/**
	 * The group (array) in self::$data that we want to add pairs to. Usually
	 * decreed by the inheriting class.
	 *
	 * @var string
	 */
	protected static $group = '';


	/**
	 * Adds the collection of names:values (in [identifier => string, ...] format
	 * to the list of data to be turned into a JS object.
	 *
	 * @param array $vars
	 */
	public static function add(array $vars) {
		if (empty(self::$vars)) self::setupPrinter();

		// Work inside a "group" or in the root?
		if (empty(static::$group)) $array =& self::$data;
		else {
			if (!isset(self::$data[static::$group]))
				self::$data[static::$group] = array();
			$array =& self::$data[static::$group];
		}

		$array = array_merge($array, $vars);
	}


	/**
	 * Creates a new action (admin|public sensitive) that will print out a JS object
	 * in the footer containing all the data added so far. This can only be called effectively
	 * once.
	 */
	protected static function setupPrinter() {
		// Enforce "run once" policy
		static $calls = 0;  $calls++; if ($calls > 1) return;

		// Choose the appropriate hook then setup the action
		$hook = (is_admin()) ? 'admin_footer' : 'wp_footer';
		add_action($hook, array(__CLASS__, 'printObject'));
	}


	/**
	 * Creates a JS object definition from the translatable strings and prints out
	 * within a script element.
	 */
	public static function printObject() {
		echo '<script type="text/javascript"> var directorium = '
			.json_encode(self::$data)
			.'</script>';
	}
}