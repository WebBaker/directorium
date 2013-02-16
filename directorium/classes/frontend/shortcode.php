<?php
namespace Directorium\Frontend;
use Directorium;


class Shortcode {
	public function __construct() {
		add_shortcode('directorium', array($this, 'shortcodeHandler'));
	}


	/**
	 * Handles [directorium] shortcode requests.
	 *
	 * By default "list", "edit" and "index" forms can be generated. Plugins can extend the range
	 * of the shortcode - to add new forms for instance - by filtering directoriumShortcodeExtension
	 * which runs when something non-standard has been requested.
	 *
	 * @param array $args
	 * @return mixed|string|void
	 */
	public function shortcodeHandler(array $args = array()) {
		if (!isset($args['form'])) return '';

		switch ($args['form']) {
			case 'list': return \Directorium\Core()->frontAdmin->listListings(); break;
			case 'edit': return \Directorium\Core()->frontAdmin->editListing(); break;
			case 'index': return \Directorium\Core()->frontend->directoryIndexPage(); break;
			default: return apply_filters('directoriumShortcodeExtension', '', $args); break;
		}
	}
}