<?php
namespace Directorium\Frontend;
use Directorium\Owners as Owners;
use Directorium\Listing as Listing;
use Directorium\Helpers\ListingData as ListingData;
use Directorium\Helpers\View as View;


class FrontAdmin {
	const PERMISSION_DENIED = 100;
	const FILE_UPLOAD_ERROR = 110;
	const LIMIT_EXCEEDED = 120;
	const NOT_PUBLISHED = 200;
	const USING_AMENDMENT = 210;
	const NEW_EDIT = 220;
	const AMENDMENT_DESTROYED = 230;
	const MASTER_TAKEN_OFFLINE = 240;


	/**
	 * @var Listing
	 */
	protected $listing;
	protected $errors = array();
	protected $notices = array();
	protected $fields = array();


	public function __construct() {
		add_filter('directoriumFrontEditorSubmission', array($this, 'submittedTaxonomyTermsFilter'), 10);
		add_filter('directoriumFrontEditorSubmission', array($this, 'submittedFieldsFilter'), 20);
		add_action('directoriumInit', array($this, 'listingUpdates'));
	}


	/**
	 * Handles updating the listing, checks first being made to ensure that any requested actions are
	 * being made by an authorized user.
	 *
	 * Kill amendment requests will result in the listing effectively reverting to the master post;
	 * a take offline request will result in any changes to the amendment being saved - however the master
	 * will be taken offline (assuming it is already in a published state).
	 */
	public function listingUpdates() {
		// Safety and sanity checks
		if (!$this->editorSubmission()) return;
		if (!$this->hasAuthorityToUpdate()) return;
		$this->listing->switchToAmendment();

		// Handle kill amendment requests
		if ($this->amendmentHasBeenKilled()) return; // Handling changes is now pointless!

		// Perform update
		$this->getUpdateFields();
		$this->listing->safeAmendment($this->fields);
		$this->handleImageChanges();

		// Take offline requests
		$this->handleTakeOfflineRequests(); // Other changes to the amendment will be preserved
	}


	protected function editorSubmission() {
		return isset($_POST['listingid']);
	}


	protected function hasAuthorityToUpdate() {
		$currentUser = wp_get_current_user();

		// Basic security check
		if (!wp_verify_nonce($_POST['validatelistingupdate'], 'listingsubmission')) $fail = true;

		// Ensure this refers to an actual listing
		$this->listing = \Directorium\Core()->listingAdmin->getPost($_POST['listingid']);
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


	/**
	 * If a kill-amendment request has been sent then this will destroy the amendment
	 * and return true, else it returns bool false.
	 *
	 * @return bool
	 */
	protected function amendmentHasBeenKilled() {
		if (!isset($_POST['kill-amendment'])) return false;
		$success = $this->listing->killAmendment();

		$this->notices[self::AMENDMENT_DESTROYED] = __('The previous amendment was destroyed.', 'directorium');
		return $success;
	}


	/**
	 * If a take-offline request has been sent then this will change the master post's status
	 * from "publish" to "draft" (only if it is already set to "publish").
	 */
	protected function handleTakeOfflineRequests() {
		if ($this->listing->originalPost->post_status !== 'publish') return;
		$this->listing->takeMasterOffline();
		$this->notices[self::MASTER_TAKEN_OFFLINE] = __('The master listing has been taken offline', 'directorium');
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
		$expected = array('ID', 'post_title', 'post_content', 'taxonomyTerms', 'listingremoveimage');

		foreach (\Directorium\Core()->listingAdmin->customFields as $alsoExpected)
			$expected[] = $alsoExpected[0];

		$expected = apply_filters('directoriumExpectedAmendmentFields', $expected);

		return array_intersect_key($fields, array_flip($expected));
	}


	/**
	 * Handles the removal and addition of images to amendments.
	 *
	 * @todo do not allow more images to be attached if already at capacity
	 */
	protected function handleImageChanges() {
		// Handle deletions first, so we don't unnecessarily limit new uploads
		$this->handleImageDeletions();

		$maxImages = $this->listing->getLimit('image');
		$imageCount = $this->listing->getImageCount();

		if ($maxImages === -1) $allowed = $maxImages;
		elseif ($imageCount < $maxImages) $allowed = $maxImages - $imageCount;
		else $allowed = 0;

		$this->addNewImages((int) $allowed);
	}


	/**
	 * Deletes images from a listing amendment as requested.
	 */
	protected function handleImageDeletions() {
		if (isset($_POST['listingremoveimage']))
			$toRemove = (array) $_POST['listingremoveimage'];

		$this->listing->removeImageAttachments($toRemove);
	}


	/**
	 * Looks in the $_FILES superglobal for uploaded image files and attempts to attach
	 * them to the listing - up to the maximum number permitted by $upto (not passing
	 * a value or setting it to -1 removes any limitation).
	 *
	 * Example: 10 images have been submitted but $upto is 4 - only the first four will
	 * be processed.
	 *
	 * @param $upto = -1
	 */
	protected function addNewImages($upto = -1) {
		if (!isset($_FILES) or count($_FILES) < 1) return;
		$count = 0;

		foreach ($_FILES as $name => $filedata) {
			// Don't exceed the limit imposed by $upto (unless it is unlimited, ie -1)
			if ($upto !== -1 and $count >= $upto) break;

			// Submitted image files are expected to have a form name beginning "newlistingimage"
			if (strpos($name, 'newlistingimage') !== 0) continue;
			else $count++;

			// Skip if there was a problem with the file (or if a file input was empty/unused)
			if ($filedata['error'] !== UPLOAD_ERR_OK and $filedata['error'] !== UPLOAD_ERR_NO_FILE) {
				$this->errors[self::FILE_UPLOAD_ERROR] = __('One or more images failed to upload without errors', 'directorium');
				continue;
			}

			// Attach!
			$this->listing->attachImage($name);
		}
	}


	/**
	 * Generates a list of listings that belong to the currently logged in
	 * user.
	 *
	 * @return string
	 */
	public function listListings() {
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
	public function editListing() {
		$currentUser = wp_get_current_user();
		$isLoggedIn = false;
		$this->listingID = isset($_REQUEST['listing']) ? absint($_REQUEST['listing']) : null;
		$this->listing = \Directorium\Core()->listingAdmin->getPost($this->listingID);

		// Is it a valid listing?
		if ($this->listing === false) return new View('listings-editor-404');

		// Load the amended version (if one exists)
		if ($this->listing->hasPendingAmendment())
			$this->listing->switchToAmendment();

		// Is the user logged in?
		if (is_a($currentUser, 'WP_User') and $currentUser->exists()) $isLoggedIn = true;

		// Does the user have ownership?
		if (!$isLoggedIn or !Owners::hasOwnership($currentUser->ID, $this->listing->originalID))
			return new View('listings-editor-401');

		// Check if within allowed limits]
		$this->checkWithinLimits();
		$this->generalNotices();
		
		$tplVars = array(
			'action' => $this->editorLink($this->listing->originalID),
			'public' => $this,
			'user' => $currentUser,
			'isLoggedIn' => $isLoggedIn,
			'listing' => $this->listing,
			'errors' => $this->errors,
			'notices' => $this->notices
		);

		$this->setupJSEditorVars();
		$tplVars = array_merge($tplVars, $this->editorFieldVars());
		return new View('listings-editor', $tplVars);
	}


	protected function checkWithinLimits() {
		$outOfBounds = array();

		$wordLimit = $this->listing->getLimit('word');
		$charLimit = $this->listing->getLimit('char');
		$btypesLimit = $this->listing->getLimit('btypes');
		$geosLimit = $this->listing->getLimit('geos');
		$imgsLimit = $this->listing->getLimit('images');

		if ($wordLimit !== -1 and $wordLimit < $this->listing->getWordCount())
			$outOfBounds[] = __('Permitted number of words has been exceeded', 'directorium');

		if ($charLimit !== -1 and $charLimit < $this->listing->getCharacterCount())
			$outOfBounds[] = __('Maximum number of characters has been exceeded', 'directorium');

		if ($btypesLimit !== -1 and $btypesLimit < $this->listing->getTaxonomyCount(Listing::TAX_BUSINESS_TYPE))
			$outOfBounds[] = __('More business types have been selected than are permitted', 'directorium');

		if ($geosLimit !== -1 and $geosLimit < $this->listing->getTaxonomyCount(Listing::TAX_GEOGRAPHY))
			$outOfBounds[] = __('More geographies have been selected than are permitted', 'directorium');

		if ($imgsLimit !== -1 and $imgsLimit < $this->listing->getImageCount())
			$outOfBounds[] = __('Maximum number of attached images has been exceeded', 'directorium');

		$warningList = '<ul>';
		foreach ($outOfBounds as $issue) $warningList .= '<li>'.$issue.'</li>';
		$warningList .= '</ul>';

		if (count($outOfBounds) > 0)
			$this->errors[self::LIMIT_EXCEEDED] = __('One or more limits have been exceeded for this listing; you should '
				.'review your listing and make appropriate changes &ndash; otherwise parts of your listing may be truncated '
				.'or may not display as you would expect. ', 'directorium').$warningList;
	}


	protected function generalNotices() {
		// If the original listing is not "published"
		if ($this->listing->originalPost->post_status !== 'publish')
			$this->notices[self::NOT_PUBLISHED] = __('This listing has not yet been approved for publication (or you have '
				.'taken it offline).', 'directorium');

		// If there is an amendment already (ie, that is the post they will be editing)
		if ($this->listing->hasPendingAmendment())
			$this->notices[self::USING_AMENDMENT] = __('You are editing an amended version of the listing (to restore the master '
				.'version please use the <em>Cancel Amendment</em> button).', 'directorium');

		// If an amendment does not currently exist (they are starting with a copy of the original post)
		else $this->notices[self::NEW_EDIT] = __('Editing this listing will create an amendment which will need to be approved '
			.'by the site administrator.', 'directorium');
	}


	protected function setupJSEditorVars() {
		ListingData::add(array(
			'maxChars' => $this->listing->getLimit('char'),
			'maxImages' => $this->listing->getLimit('image'),
			'maxWords' => $this->listing->getLimit('word'),
			'maxGeos' => $this->listing->getLimit('geos'),
			'maxBtypes' => $this->listing->getLimit('btypes'),
			'usageFormat' => __('%d of %d', 'directorium') // The %d placeholders must survive translation
		));
	}


	protected function getEditorFormAction() {
		$core = \Directorium\Core();
		$baseURL = $core->settings->get('general.editorPage');

		if (isset($_GET['listing'])) $query = array('listing' => absint($_GET['listing']));
		else $query = null;

		return URL::generate($baseURL, $query);
	}


	/**
	 * Populates the variables used for key post variables in the editor.
	 */
	protected function editorFieldVars() {
		$defaults = array('title', 'content');
		$defaults = array_fill_keys($defaults, '');

		// Sticky form data (from the post array)
		foreach ($defaults as $key => $value) {
			$postkey = "listing$key";
			if (isset($_POST[$postkey])) $defaults[$key] = $_POST[$postkey];
		}

		// Override with post fields from the db
		if (isset($this->listing->post)) foreach ($defaults as $key => $value) {
			$postfield = "post_$key";
			if (isset($this->listing->post->$postfield)) $defaults[$key] = $this->listing->post->$postfield;
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
		$editorSlug = \Directorium\Core()->settings->get('general.editorPage');
		$editorSlug = trailingslashit($editorSlug);
		$editorQuery = '?listing='.absint($listingID);
		$editorURL = trailingslashit(home_url()).$editorSlug.$editorQuery;
		return apply_filters('directorium_editor_link', $editorURL, $listingID);
	}
}