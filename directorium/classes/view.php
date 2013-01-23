<?php
namespace Directorium;


class View {
	protected $path = null;
	protected $vars = null;


	/**
	 * Render and print the view file.
	 */
	public static function write($view, array $vars = null) {
		$class = __CLASS__;
		$view = new $class($view, $vars);
		echo $view;
	}


	/**
	 * Render the view file and return as a string.
	 */
	public static function load($view, array $vars = null) {
		$class = __CLASS__;
		$view = new $class($view, $vars);
		return $view->__toString();
	}


	public function __construct($view, array $vars = null) {
		$this->makePath($view);
		if ($vars !== null) $this->vars = $vars;
	}


	/**
	 * Intelligently determines the path to the view (in views/admin for admin requests
	 * or else views/public).
	 *
	 * @todo cascading template system: allow theme template overrides
	 *
	 * @param $view
	 */
	protected function makePath($view) {
		$path = str_replace('.', '', $view);
		$type = (is_admin()) ? '/views/admin' : '/views/public';
		$path = Core::$plugin->dir."$type/$path.php";

		if (file_exists($path)) $this->path = $path;
	}


	public function __toString() {
		ob_start();
		if ($this->vars !== null) extract($this->vars);
		include $this->path;
		return ob_get_clean();
	}
}