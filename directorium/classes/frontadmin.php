<?php
namespace Directorium;


class FrontAdmin {
	public function __construct() {
		add_shortcode('directorium', array($this, 'shortcodeHandler'));
	}


	public function shortcodeHandler(array $args = array()) {
		if (!isset($args['form'])) return '';

		switch ($args['form']) {
			case 'list': return $this->listListings(); break;
			case 'edit': return $this->editListing(); break;
		}
	}


	/**
	 * Generates a list of listings that belong to the currently logged in
	 * user.
	 *
	 * @return string
	 */
	protected function listListings() {
		$currentUser = wp_get_current_user();
		$isLoggedIn = false;
		$listings = array();

		if (is_a($currentUser, 'WP_User') and $currentUser->exists()) {
			$listings = Owners::getListingsForUser($currentUser->ID);
			$isLoggedIn = true;
		}

		return new View('listings-list', array(
			'public' => $this,
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listings' => $listings
		));
	}


	/**
	 * Allows the currently logged in user to edit one of his listings (determined
	 * by $_REQUEST['listing']).
	 *
	 * @return string
	 */
	protected function editListing() {
		$currentUser = wp_get_current_user();
		$isLoggedIn = false;
		$listing = new Listing($_REQUEST['listing']);

		if (is_a($currentUser, 'WP_User') and $currentUser->exists())
			$isLoggedIn = true;

		return new View('listings-editor', array(
			'public' => $this,
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listing' => $listing
		));
	}


	/**
	 * Returns a URL to the frontend listing editor; this depends on the
	 * general.editorPage setting if it is to be correctly formed.
	 *
	 * @param int $listingID
	 * @return string
	 */
	public function editorLink($listingID) {
		$editorSlug = Core::$plugin->settings->get('general.editorPage');
		$editorSlug = trailingslashit($editorSlug);
		$editorQuery = '?listing='.absint($listingID);
		$editorURL = trailingslashit(site_url()).$editorSlug.$editorQuery;
		return apply_filters('directorium_editor_link', $editorURL, $listingID);
	}
}