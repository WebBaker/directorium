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
		add_action('submitpost_box', array($this, 'insertAmendmentMarker'));
		add_action('admin_init', array($this, 'saveToAmendment'), 1);
		add_filter('media_view_settings', array($this, 'useAmendmentIDforUploads'));
		add_action('save_post', array($this, 'disposeOfAmendment'));
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

			$listadmin = Core()->listingAdmin;
			$this->listing = $listadmin->getPost($listadmin->getListingID());
			$this->listing->switchToAmendment();
		}
		else {
			$this->listing = $listing;
			$this->listing->switchToAmendment();
		}

		$this->swapPostFields();
		$this->isAmendment = true;
		add_filter('get_sample_permalink_html', array($this, 'amendmentSamplePermalink'));
	}


	/**
	 * Alters any of the post fields for which we have an amendment.
	 */
	protected function swapPostFields() {
		$GLOBALS['post'] = $this->listing->post;
	}


	/**
	 * Adds a hidden field to help detect when an amendment has been accepted.
	 */
	public function insertAmendmentMarker() {
		if ($this->isAmendment) { // Not an amendment? Leave no marker!
			$listadmin = Core()->listingAdmin;
			$this->listing = $listadmin->getPost($listadmin->getListingID());
			$this->listing->switchToAmendment();

			$id = $this->listing->id;
			echo '<input type="hidden" name="directoriumAmendment" value="'.$id.'" />';
		}
	}


	/**
	 * New media uploads will be attached to the primary (published) listing unless we
	 * modify the media view settings array - which we do here, making the post ID that of
	 * the amendment.
	 *
	 * @param array $params
	 * @return array
	 */
	public function useAmendmentIDforUploads($params) {
		// Only if the amendment has been loaded!
		if (!isset($_GET['loadalternative']) or $_GET['loadalternative'] !== 'amendment') return $params;

		if (isset($params['post']['id'])) {
			$listing = Core()->listingAdmin->getPost($params['post']['id']);
			$amendment = $listing->getAmendmentData();
			if (is_int($amendment->ID)) $params['post']['id'] = $amendment->ID;
		}

		return $params;
	}


	/**
	 * Make sure if an amendment is saved that it is indeed the amendment and not the published
	 * listing which is affected.
	 */
	public function saveToAmendment() {
		// We are interested only in amendment save operations (definitely not "publish" operations)
		if (isset($_POST['publish']) or !isset($_POST['directoriumAmendment']) or !isset($_POST['save'])) return;
		$originalPostedID = $_POST['post_ID'];

		// Set up the amendment as the current post
		$_POST['post_ID'] = $_POST['directoriumAmendment'];
		$this->flipToAmendment(Core()->listingAdmin->getPost($_POST['post_ID']));

		// Safely spoof the nonce to workaround the switch in post ID
		check_admin_referer('update-post_'.$originalPostedID);
		$_REQUEST['_wpnonce'] = wp_create_nonce('update-post_'.$_POST['post_ID']);

		// Take control of the redirect operation
		add_filter('redirect_post_location', array($this, 'amendmentUpdateRedirect'), 500, 2);
	}


	/**
	 * If the redirect relates to an amendment, hijack to form the anticipated link.
	 *
	 * @param $location
	 * @param $postID
	 */
	public function amendmentUpdateRedirect($location, $postID) {
		$listing = Core()->listingAdmin->getPost($postID);
		if ($listing === false or !$listing->isAmendment) return $location;

		$postParam = 'post='.$listing->originalID;
		$defaultPostParam = 'post='.$postID;

		$location = str_replace($defaultPostParam, $postParam, $location).'&loadalternative=amendment';
		return $location;
	}


	/**
	 * Modifies the sample permalink (when amendments are being viewed).
	 *
	 * @param string $html
	 * @return string
	 */
	public function amendmentSamplePermalink($html) {
		$startOfURL = strpos($html, '<span');
		$html = substr($html, 0, $startOfURL);
		$html .= '<span>'.__('to be determined (you are previewing an emendment)', 'directorium').'</span>';
		return $html;
	}


	/**
	 * Once an amendment has been published, dispose of it.
	 */
	public function disposeOfAmendment() {
		// Avoid an infinite loop - killAmendment() uses API functions that trigger the save_post action
		// which triggers this method
		remove_action('save_post', array($this, 'disposeOfAmendment'));

		if (isset($_POST['publish']) and isset($_POST['directoriumAmendment'])) {
			$amendment = Core()->listingAdmin->getPost($_POST['directoriumAmendment']);

			// Be sure it really is an amendment then wipe it out
			if ($amendment->isAmendment) $amendment->killAmendment();
		}
	}
}