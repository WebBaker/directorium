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

		if (is_a($currentUser, WP_User) and $currentUser->exists()) {
			$listings = Owners::getListingsForUser($currentUser->ID);
			$isLoggedIn = true;
		}

		return new View('listings-list', array(
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listings' => $listings
		));
	}


	/**
	 * Allows the currently logged in user to edit one of his listings (determined
	 * by $_REQUEST['listingid'].
	 *
	 * @return string
	 */
	protected function editListing() {

	}
}