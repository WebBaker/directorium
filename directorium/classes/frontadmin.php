<?php
namespace Directorium;


class FrontAdmin {
	const PERMISSION_DENIED = 100;

	protected $listing;
	protected $errors = array();
	protected $fields = array();


	public function __construct() {
		add_shortcode('directorium', array($this, 'shortcodeHandler'));
		add_filter('directoriumFrontEditorSubmission', array($this, 'submittedTaxonomyTermsFilter'), 10);
		add_filter('directoriumFrontEditorSubmission', array($this, 'submittedFieldsFilter'), 20);
		add_action('directoriumInit', array($this, 'listingUpdates'));
	}


	public function listingUpdates() {
		// Safety and sanity checks
		if (!$this->editorSubmission()) return;
		if (!$this->hasAuthorityToUpdate()) return;

		// Perform update
		$this->getUpdateFields();
		$this->listing->safeAmendment($this->fields);
	}


	protected function editorSubmission() {
		return isset($_POST['listingid']);
	}


	protected function hasAuthorityToUpdate() {
		$currentUser = wp_get_current_user();

		// Basic security check
		if (!wp_verify_nonce($_POST['validatelistingupdate'], 'listingsubmission')) $fail = true;

		// Ensure this refers to an actual listing
		$this->listing = Core()->listingAdmin->getPost($_POST['listingid']);
		if (!$this->listing) $fail = true;

		// User is logged in and has ownership of the listing
		if (!is_a($currentUser, 'WP_User') or !$currentUser->exists()) $fail = true;
		if (!isset($currentUser->ID) or !$this->listing or !Owners::hasOwnership($currentUser->ID, $this->listing->id)) $fail = true;

		if (isset($fail) and $fail === true) {
			$this->errors[self::PERMISSION_DENIED] = __('You do not have permission to make the change you requested, or '
				.'else you waited too long before submitting your change. Please try again.', 'directorium');
			return false;
		}

		return true;
	}


	protected function getUpdateFields() {
		$this->fields = array_merge($_POST, array(
			'ID' => $this->listing->id,
			'post_title' => $_POST['listingtitle'],
			'post_content' => $_POST['listingcontent']
		));

		unset($this->fields['post_type'], $this->fields['post_parent']);

		$this->fields = apply_filters('directoriumFrontEditorSubmission', $this->fields);
	}


	/**
	 * Scrutinizes any submitted taxonomy terms, ready for further processing.
	 *
	 * @param array $fields
	 * return array
	 */
	public function submittedTaxonomyTermsFilter(array $fields) {
		$terms = array();

		// Only terms for allowed taxonomies may be submitted
		$allowedTaxonomies = apply_filters('directoriumAllowedTaxonomies', array(
			Listing::TAX_BUSINESS_TYPE, Listing::TAX_GEOGRAPHY
		));

		// Build a list of allowed terms
		foreach ($allowedTaxonomies as $taxonomy) {
			if (isset($fields['term-selection'][$taxonomy]))
				$terms[$taxonomy] = $fields['term-selection'][$taxonomy];
			// Workaround to allow total clear out of terms:
			else $terms[$taxonomy] = array(0 => 0);
		}

		// Discard the original term-selection element
		unset($fields['term-selection']);

		// Convert term IDs from keys to fields
		foreach ($terms as $taxonomy => $termList)
			$terms[$taxonomy] = array_keys($termList);

		// And add back to the array of fields under "taxonomyTerms"
		$fields['taxonomyTerms'] = $terms;
		return $fields;
	}


	/**
	 * Enforces a whitelist approach to submitted form fields (for frontend-submitted amendments).
	 *
	 * @param array $fields
	 * @return array
	 */
	public function submittedFieldsFilter(array $fields) {
		// Derive a list of expected field keys
		$expected = array('ID', 'post_title', 'post_content', 'taxonomyTerms');

		foreach (Core()->listingAdmin->customFields as $alsoExpected)
			$expected[] = $alsoExpected[0];

		$expected = apply_filters('directoriumExpectedAmendmentFields', $expected);

		return array_intersect_key($fields, array_flip($expected));
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
		$listing = Core()->listingAdmin->getPost($listingID);

		// Is it a valid listing?
		if ($listing === false)
			return new View('listings-editor-404');

		// Load the amended version (if one exists)
		if ($listing->hasPendingAmendment())
			$listing->switchToAmendment();

		// Is the user logged in?
		if (is_a($currentUser, 'WP_User') and $currentUser->exists())
			$isLoggedIn = true;

		// Does the user have ownership?
		if (!$isLoggedIn or !Owners::hasOwnership($currentUser->ID, $listing->originalID))
			return new View('listings-editor-401');

		$tplVars = array(
			'action' => $this->editorLink($listing->id),
			'public' => $this,
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listing' => $listing,
			'errors' => $this->errors
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