<?php
namespace Directorium\Frontend;
use Directorium\Listing as Listing;


class Frontend {
	protected $defaultScriptsEnabled = false;
	protected $defaultStylesEnabled = false;
	protected $themeStylesEnabled = false;


	public function __construct() {
		$this->defaultScriptsEnabled = \Directorium\Core()->settings->get('presentation.enableDefaultScripts');
		$this->defaultStylesEnabled = \Directorium\Core()->settings->get('presentation.enableDefaultStyles');
		$this->themeStylesEnabled = \Directorium\Core()->settings->get('presentation.enableThemeStyles');

		if ($this->defaultStylesEnabled or $this->themeStylesEnabled or $this->defaultScriptsEnabled)
			add_action('the_posts', array($this, 'setupAssets'));
	}


	/**
	 * Detect if a directory page is being viewed, or a page that includes directory content.
	 * If this is positive then enqueue the frontend stylesheets.
	 *
	 * @param array $posts
	 */
	public function setupAssets(array $posts) {
		if (empty($posts)) return $posts;
		$directoryContent = false;

		// Iterate through the found posts *only* until we get a positive match
		foreach ($posts as $post)
			// We've got a match whenever a Listing is included or another post with a directorium shortcode is included
			if ($post->post_type === Listing::POST_TYPE or strpos($post->post_content, '[directorium ') !== false) {
				$directoryContent = true;
				break;
			}

		if ($directoryContent) {
			$this->addStylesheets();
			$this->addScripts();
		}
		return $posts;
	}


	protected function addStylesheets() {
		if ($this->defaultStylesEnabled)
			wp_enqueue_style('directoriumDefaultStylesheet', \Directorium\Core()->url.'assets/directory.css');
	}


	protected function addScripts() {
		if ($this->defaultScriptsEnabled)
			wp_enqueue_script('directoriumDefaultScripts', \Directorium\Core()->url.'assets/directory.js', array('jquery'));
	}
}