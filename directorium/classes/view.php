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


	protected function makePath($view) {
		$path = str_replace('.', '', $view);
		$path = Core::$plugin->dir."/views/$path.php";

		if (file_exists($path)) $this->path = $path;
	}


	public function __toString() {
		ob_start();
		if ($this->vars !== null) extract($this->vars);
		include $this->path;
		return ob_get_clean();
	}
}