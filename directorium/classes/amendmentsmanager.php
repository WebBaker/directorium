<?php
namespace Directorium;


/**
 * Creates a user interface and workflow to allow easier handling of listing amendments.
 */
class AmendmentsManager {
	public $handle;
	public $notices = array();
	protected $listing;
	protected $isAmendment = false;
	protected $amendment = array();


	public function __construct() {
		add_action('admin_head-post.php', array($this, 'flipToAmendment'));
		add_action('directorium_viewing_listing', array($this, 'flipToAmendment'));
		add_action('submitpost_box', array($this, 'insertAmendmentMarker'));
		add_action('save_post', array($this, 'makeAmendmentLive'));
	}


	/**
	 * If the amended version of a (Listing) post has been requested and is
	 * available then this method injects it into the $post global and the WP
	 * cache (in order to make custom field data available also).
	 *
	 * When used outside of the post editor (ie, within the admin environment)
	 * a Listing object can be passed in.
	 */
	public function flipToAmendment($listing = null) {
		if (!is_a($listing, 'Directorium\\Listing')) {
			if (!isset($_GET['loadalternative']) or $_GET['loadalternative'] !== 'amendment') return;
			if (!isset($GLOBALS['post']) or !is_object($GLOBALS['post'])) return;
			$this->listing = Listing::getPost(Listing::getListingID());
		}
		else {
			$this->listing = $listing;
		}

		$this->amendment = $this->listing->getAmendmentData();
		$this->isAmendment = true;

		$this->swapPostFields($listing);
		$this->injectCustomFields();
	}


	/**
	 * Alters any of the post fields for which we have an amendment. Operates on the global
	 * $post object unless a Listing object is passed in.
	 */
	protected function swapPostFields($listing = null) {
		if (!is_a($listing, 'Directorium\\Listing')) global $post;
		else $post = $listing->post;

		// Change the post object and leave the amendment array only with custom fields
		foreach ($post as $field => $value)
			if (isset($this->amendment[$field])) {
				$post->$field = $this->amendment[$field];
				unset($this->amendment[$field]);
			}
	}


	/**
	 * Places any custom fields held as amendment data into the cache (so that
	 * they are retrieved when get_post_meta() etc are used).
	 */
	protected function injectCustomFields() {
		if (empty($this->amendment)) return;
		global $wp_object_cache;

		foreach ($this->amendment as $customfield => $value) {
			// Prefix any custom fields registered with the Listing class
			if ($this->listing->isCustomListingField($customfield))
				$customfield = "_$customfield";

			// Inject into cache
			$wp_object_cache->cache['post_meta'][$this->listing->id][$customfield] = array($value);
		}
	}


	/**
	 * Adds a hidden field to help detect when an amendment has been accepted.
	 */
	public function insertAmendmentMarker() {
		if ($this->isAmendment)
			echo '<input type="hidden" name="directoriumAmendment" value="1" />';
	}


	/**
	 * Removes the amendment meta data, making the newly saved post the de facto
	 * listing.
	 *
	 * Expects to be called when the save_post action fires.
	 */
	public function makeAmendmentLive() {
		if (!isset($_POST['directoriumAmendment']) or $_POST['directoriumAmendment'] !== '1' or !isset($_POST['ID']))
			return;

		$postID = absint($_POST['ID']);
		delete_post_meta($postID, '_directoriumAmendment');
	}
}