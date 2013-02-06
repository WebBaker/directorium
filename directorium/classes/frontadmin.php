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
		$listingID = isset($_REQUEST['listing']) ? absint($_REQUEST['listing']) : null;
		$listing = new Listing($listingID);

		// Load the amended version (if one exists)
		if ($listing->hasPendingAmendment())
			do_action('directorium_viewing_listing', $listing);

		if (is_a($currentUser, 'WP_User') and $currentUser->exists())
			$isLoggedIn = true;

		$tplVars = array(
			'action' => $this->getEditorFormAction(),
			'public' => $this,
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listing' => $listing
		);

		$tplVars = array_merge($tplVars, $this->editorFieldVars($listing));
		return new View('listings-editor', $tplVars);
	}


	protected function getEditorFormAction() {
		$core = Core::$plugin;
		$baseURL = $core->settings->get('general.editorPage');

		if (isset($_GET['listing'])) $query = array('listing' => absint($_GET['listing']));
		else $query = null;

		return URL::generate($baseURL, $query);
	}


	/**
	 * Populates the variables used for key post variables in the editor.
	 */
	protected function editorFieldVars($listing) {
		$defaults = array('title', 'content');
		$defaults = array_fill_keys($defaults, '');

		if (isset($listing->post)) foreach ($defaults as $key => $value) {
			$postfield = "post_$key";
			if (isset($listing->post->$postfield)) $defaults[$key] = $listing->post->$postfield;
		}

		foreach ($defaults as $key => $value) {
			$postkey = "listing$key";
			if (isset($_POST[$postkey])) $defaults[$key] = $_POST[$postkey];
		}

		return $defaults;
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
		$editorURL = trailingslashit(home_url()).$editorSlug.$editorQuery;
		return apply_filters('directorium_editor_link', $editorURL, $listingID);
	}
}