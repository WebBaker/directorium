<?php
namespace Directorium;


class Frontend {
	protected $defaultStylesEnabled = false;
	protected $themeStylesEnabled = false;


	public function __construct() {
		$this->defaultStylesEnabled = Core::$plugin->settings->get('presentation.enableDefaultStyles');
		$this->themeStylesEnabled = Core::$plugin->settings->get('presentation.enableThemeStyles');

		if ($this->defaultStylesEnabled or $this->themeStylesEnabled)
			add_action('the_posts', array($this, 'setupStylesheets'));
	}


	/**
	 * Detect if a directory page is being viewed, or a page that includes directory content.
	 * If this is positive then enqueue the frontend stylesheets.
	 *
	 * @param array $posts
	 */
	public function setupStylesheets(array $posts) {
		if (empty($posts)) return $posts;
		$directoryContent = false;

		// Iterate through the found posts *only* until we get a positive match
		foreach ($posts as $post)
			// We've got a match whenever a Listing is included or another post with a directorium shortcode is included
			if ($post->post_type === Listing::POST_TYPE or strpos($post->post_content, '[directorium ') !== false) {
				$directoryContent = true;
				break;
			}

		if ($directoryContent) $this->addStylesheets();
		return $posts;
	}


	protected function addStylesheets() {
		if ($this->defaultStylesEnabled)
			wp_enqueue_style('directorium_default_stylesheet', Core::$plugin->url.'assets/directory.css');
	}
}