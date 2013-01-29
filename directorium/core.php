<?php
namespace Directorium;


class Core {
	public static $plugin = null;
	public $dir;
	public $url;
	public $settings;


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
		new ListingAdmin;
		new AmendmentsManager;
		new Importer;
		new Frontend;
		new FrontAdmin;
	}
}


Core::init();