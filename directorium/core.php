<?php
namespace Directorium;
use Directorium\Helpers;
use Directorium\Frontend\Frontend as Frontend;
use Directorium\Frontend\FrontAdmin as FrontAdmin;


class Core {
	public static $plugin = null;
	public $dir;
	public $url;
	public $listingAdmin;
	public $settings;
	public $amendmentsManager;
	public $importer;
	public $frontend;
	public $frontAdmin;



	public static function init() {
		if (self::$plugin === null) {
			spl_autoload_register(array(__CLASS__, 'classLoader'));
			$coreClass = __CLASS__;
			self::$plugin = new $coreClass();
		}
	}


	public static function classLoader($class) {
		// We are only interested in Directorium-namespaced classes
		$path = explode('\\', $class);
		if (count($path) < 2 or $path[0] !== 'Directorium') return;

		$path[0] = 'classes';
		$path = implode(DIRECTORY_SEPARATOR, $path);
		$path = __DIR__.DIRECTORY_SEPARATOR.strtolower($path).'.php';

		if (file_exists($path)) include_once $path;
	}


	public function __construct() {
		$this->dir = __DIR__;
		$this->url = plugin_dir_url(__FILE__);
		add_action('init', array($this, 'loadComponents'));
	}


	public function loadComponents() {
		$this->settings = new Settings;
		$this->listingAdmin = new ListingAdmin;
		$this->amendmentsManager = new AmendmentsManager;
		$this->importer = new Importer;
		$this->frontend = new Frontend;
		$this->frontAdmin = new FrontAdmin;
	}
}


Core::init();


// Core functions follow...

/**
 * Returns the Core Plugin object. This is just a convenience, it can anyway
 * be accessed via \Directorium\Core::$plugin.
 *
 * @return \Directorium\Core
 */
function core() {
	return Core::$plugin;
}