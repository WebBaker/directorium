<?php
namespace Directorium;
use Exception as Exception;


class ListingAdmin {
	const CUSTOM_FIELDS = 'directorium_custom_fields';
	const EDITORIAL_CONTROLS = 'directorium_editorial_meta';
	const MEDIA_CONTROLS = 'directorium_media_meta';
	const OWNER_CONTROLS = 'directorium_owner_meta';
	const IMG_PREVIEW_SIZE = 'directorium_preview_thumb';
	const FIELD_NAME = 0;
	const FIELD_GROUP = 3;
	const MSG_PENDING_AMENDMENT = 100;
	const MSG_VIEWING_AMENDMENT = 110;
	const UNLIMITED = -1;

	protected $initialized = false;

	/**
	 * Container to store and retrieve Listing objects, avoiding the need to
	 * instantiate new objects unnecessarily.
	 *
	 * @var array
	 */
	protected $postInstances = array();
	protected $postID = null;

	/**
	 * Directory specific fields.
	 *
	 * Each field is described as an array of the pattern [name, type, label,
	 * group] where the group is primarily a means of grouping like fields in
	 * the admin environment.
	 *
	 * @var array
	 */
	public $customFields = array(
		array('directoriumAddress1', Field::TYPE_TEXT_NORMAL, 'Address 1', 1),
		array('directoriumAddress2', Field::TYPE_TEXT_NORMAL, 'Address 2', 1),
		array('directoriumCity', Field::TYPE_TEXT_NORMAL, 'City', 1),
		array('directoriumRegion', Field::TYPE_TEXT_NORMAL, 'Region', 1),
		array('directoriumPostCode', Field::TYPE_TEXT_NORMAL, 'Postal or Zip Code', 1),
		array('directoriumCountry', Field::TYPE_TEXT_NORMAL, 'Country', 1),
		array('directoriumURL', Field::TYPE_TEXT_NORMAL, 'URL', 2),
		array('directoriumEmail', Field::TYPE_TEXT_NORMAL, 'Email Address', 2),
		array('directoriumPhone', Field::TYPE_TEXT_NORMAL, 'Telephone', 2),
		array('directoriumOwner', Field::TYPE_USER, 'User/owner', 2)
	);

	public $id;
	public $post;
	public $strippedPostContent = '';
	public $postAttachments = array();
	public $postTerms = array();


	public function __construct() {
		// Initialize once only
		if ($this->initialized) return;
		$this->initialized = true;

		add_action('init', array($this, 'registerTaxonomies'), 20);
		add_action('init', array($this, 'registerPostTypes'), 30);
		add_action('init', function() { do_action('directoriumInit'); }, 50);
		add_action('admin_enqueue_scripts', array($this, 'controlAutosaves'), 5);
		$this->registerPreviewImageSize();

		// Enqueue styles for both existing and new posts
		add_action('admin_print_styles-post.php', array($this, 'editorStyles'));
		add_action('admin_print_styles-post-new.php', array($this, 'editorStyles'));
		add_action('admin_enqueue_scripts', array($this, 'editorScripts'));
	}


	/**
	 * Returns the Listing object relating to the specified post ID, or false if the
	 * specified ID does not relate to a Listing post/amendment.
	 *
	 * Allows for reuse of objects instead of generating multiple Listings relating
	 * to the same post.
	 *
	 * @param $id
	 * @return mixed Listing | (bool) false
	 */
	public function getPost($id) {
		$id = absint($id);
		if (isset($this->postInstances[$id])) return $this->postInstances[$id];
		else {
			try {
				$this->postInstances[$id] = new Listing($id);
				return $this->postInstances[$id];
			}
			catch (Exception $e) {
				return false;
			}
		}
	}


	public function registerTaxonomies() {
		register_taxonomy(Listing::TAX_BUSINESS_TYPE, null, $this->getBusinessTypeDefinition());
		register_taxonomy(Listing::TAX_GEOGRAPHY, null, $this->getGeographyDefinition());
	}


	public function getGeographyDefinition() {
		return array(
			'labels' => array(
				'name' => __('Geographies', 'directorium'),
				'singular_name' => __('Geography', 'directorium'),
				'search_items' =>  __('Search Geographies', 'directorium'),
				'all_items' => __('All Geographies', 'directorium'),
				'parent_item' => __('Parent Geography', 'directorium'),
				'parent_item_colon' => __('Parent Geography:', 'directorium'),
				'edit_item' => __('Edit Geography', 'directorium'),
				'update_item' => __('Update Geography', 'directorium'),
				'add_new_item' => __('Add New Geography', 'directorium'),
				'new_item_name' => __('New Geography Name', 'directorium'),
				'menu_name' => _x('Geographies', 'menu name', 'directorium')),
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => __('geography', 'directorium'),
				'hierarchical' => true));
	}


	public function getBusinessTypeDefinition() {
		return array(
			'labels' => array(
				'name' => __('Business Types', 'directorium'),
				'singular_name' => __('Business Type', 'directorium'),
				'search_items' =>  __('Search Business Types', 'directorium'),
				'all_items' => __('All Business Types', 'directorium'),
				'parent_item' => __('Parent Business Type', 'directorium'),
				'parent_item_colon' => __('Parent Business Type:', 'directorium'),
				'edit_item' => __('Edit Business Type', 'directorium'),
				'update_item' => __('Update Business Type', 'directorium'),
				'add_new_item' => __('Add New Business Type', 'directorium'),
				'new_item_name' => __('New Business Type Name', 'directorium'),
				'menu_name' => _x('Business Types', 'menu name', 'directorium')),
			'hierarchical' => true,
			'rewrite' => array(
				'slug' => __('type', 'directorium'),
				'hierarchical' => true));
	}


	public function registerPostTypes() {
		register_post_type(Listing::POST_TYPE, $this->getTypeDefinition());
		register_post_type(Listing::AMENDMENT_TYPE, $this->getTypeDefinition(true));

		add_action('save_post', array($this, 'saveCustomFields'));
		add_action('save_post', array($this, 'saveEditorialSettings'));
		add_action('save_post', array($this, 'saveOwnerSettings'));
		add_action('save_post', array($this, 'saveMediaChanges'));
		add_filter('post_updated_messages', array($this, 'editorMessages'));
	}


	/**
	 * Returns the definition for Listing post types. If optional param $amendment is
	 * true then the definition will be adapted for listing amendments.
	 *
	 * @param bool $amendment
	 * @return array
	 */
	public function getTypeDefinition($amendment = false) {
		$definition = array(
			'labels' => array(
				'name' => __('Directory Listings', 'directorium'),
				'singular_name' => __('Listing', 'directorium'),
				'add_new' => __('Add New', 'directorium'),
				'add_new_item' => __('Add New Listing', 'directorium'),
				'edit_item' => __('Edit Listing', 'directorium'),
				'new_item' => __('New Listing', 'directorium'),
				'all_items' => __('All Listings', 'directorium'),
				'view_item' => __('View Listing', 'directorium'),
				'search_items' => __('Search Listings', 'directorium'),
				'not_found' =>  __('No Listings Found', 'directorium'),
				'not_found_in_trash' => __('No listings found in trash', 'directorium'),
				'parent_item_colon' => '',
				'menu_name' => __('Directory', 'directorium')),
			'public' => $amendment ? false : true,
			'publicly_queryable' => $amendment ? false : true,
			'show_ui' => $amendment ? false : true,
			'show_in_menu' => $amendment ? false : true,
			'query_var' => true,
			'rewrite' =>  $amendment ? false : array(
				'slug' => _x('listing', 'slug', 'directorium')),
			'capability_type' => 'post',
			'has_archive' => $amendment ? false : true,
			'hierarchical' => false,
			'menu_position' => null,
			'register_meta_box_cb' => array(
				$this, 'metaboxes'),
			'supports' => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'comments'),
			'taxonomies' => array(
				Listing::TAX_BUSINESS_TYPE,
				Listing::TAX_GEOGRAPHY,
				'post_tag'));

		// Other cleanup work for amendments
		if ($amendment) unset($definition['labels']['menu_name'], $definition['menu_position']);

		return $definition;
	}


	public function registerPreviewImageSize() {
		add_image_size(self::IMG_PREVIEW_SIZE, 112, 112);
	}


	public function editorStyles() {
		wp_enqueue_style('directoriumEditor', Core::$plugin->url.'assets/listings-editor.css');
	}


	/**
	 * Sets up the editor script and text translations for that script.
	 */
	public function editorScripts() {
		Translatables::add(array(
			'confirmAmendment' => __("Are you sure you have checked everything including address fields and images?\n\n"
				.'Continuing means you are accepting this version of the listing. If you are unsure, cancel this '
				.'operation and check everything again!', 'directorium')
		));
		wp_enqueue_script('directoriumEditor', Core::$plugin->url.'assets/listings-editor.js', array('jquery'));
	}


	public function metaboxes() {
		add_meta_box(self::CUSTOM_FIELDS,
			__('Directory Fields', 'directorium'),
			array($this, 'customFields'),
			Listing::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::EDITORIAL_CONTROLS,
			__('Editorial Controls', 'directorium'),
			array($this, 'editorialControls'),
			Listing::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::MEDIA_CONTROLS,
			__('Attached Media', 'directorium'),
			array($this, 'mediaControls'),
			Listing::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::OWNER_CONTROLS,
			__('Listing Ownership', 'directorium'),
			array($this, 'ownerControls'),
			Listing::POST_TYPE,
			'side'
		);
	}


	/**
	 * Displays the custom fields meta box on the Listings editor page.
	 */
	public function customFields() {
		$listing = $this->getPost($this->getListingID());
		$fieldGroups = array();

		foreach ($this->customFields as $definition) {
			$field = new Field($definition[0], $definition[1], $definition[2], null, $listing->getField($definition[0]));
			$key = isset($definition[self::FIELD_GROUP]) ? $definition[self::FIELD_GROUP] : 'misc';
			$fieldGroups[$key][] = $field;
		}

		View::write('custom-fields', array(
			'listing' => $listing,
			'fieldGroups' => $fieldGroups
		));
	}


	/**
	 * Displays the editorial controls meta box on the Listings editor page.
	 */
	public function editorialControls() {
		$listing = self::getPost(self::getListingID());
		View::write('editorial-controls', array('listing' => $listing));
	}


	public function saveCustomFields() {
		if (empty($_POST) or !is_admin() or !isset($_POST['directorium_fields_check'])) return;
		if (wp_verify_nonce($_POST['directorium_fields_check'], 'directorium_custom_fields') === false) return;

		$listing = new Listing($GLOBALS['post']->ID);

		foreach ($this->customFields as $field)
			if (array_key_exists($field[0], $_POST))
				$listing->setField($field[0], $_POST[$field[0]]);
	}


	public function saveEditorialSettings() {
		if (empty($_POST)) return;
		if (!isset($_POST['directorium_editorial_check'])) return;
		if (wp_verify_nonce($_POST['directorium_editorial_check'], 'directorium_editorial_controls') === false) return;

		$listing = new Listing($GLOBALS['post']->ID);
		$listing->setLimit('word', $_POST['wordlimit']);
		$listing->setLimit('char', $_POST['charlimit']);
		$listing->setLimit('image', $_POST['imagelimit']);
		$listing->setLimit('video', $_POST['videolimit']);
		$listing->setLimit('btypes', $_POST['btypeslimit']);
		$listing->setLimit('geos', $_POST['geoslimit']);
	}


	public function mediaControls() {
		$listing = self::getPost(self::getListingID());
		View::write('media-controls', array('listing' => $listing));
	}


	public function saveMediaChanges($postID) {
		if (empty($_POST) or !is_admin() or !isset($_POST['directorium_fields_check'])) return;
		if (wp_verify_nonce($_POST['directorium_media_check'], 'directorium_media_controls') === false) return;
		if (!isset($_POST['attachment']) or !is_array($_POST['attachment'])) return;

		foreach ($_POST['attachment'] as $attID => $fields) {
			// Check for delete/detach requests
			if ($fields['action'] !== 'do-nothing') self::detachMedia($attID, $fields['action']);

			// Update attachment meta data
			$data = wp_get_attachment_metadata($attID);
			$data['image_meta']['caption'] = apply_filters('directorium_listing_attachment_caption', $fields['caption']);
			$data['image_meta']['title'] = apply_filters('directorium_listing_attachment_title', $fields['title']);

			wp_update_attachment_metadata($attID, $data);
		}
	}


	/**
	 * Detaches the media item from the post (if $nature === 'detach') or attempts to
	 * complete delete it (if $nature === 'delete').
	 *
	 * @todo delete method (currently detaches regardless of $nature)
	 *
	 * @param $mediaID
	 * @param string $nature = 'detach'
	 */
	protected function detachMedia($mediaID, $nature = 'detach') {
		switch($nature) {
			case 'detach':
				wp_update_post(array(
					'ID' => $mediaID,
					'post_parent' => 0
				));
				break;

			case 'delete':
				wp_delete_post($mediaID, true);
				break;
		}
	}


	public function ownerControls() {
		$listing = $this->getPost($this->getListingID());
		$owners = Owners::whoOwnsThis($listing->id);
		View::write('owner-controls', array('listing' => $listing, 'owners' => $owners));
	}


	public function saveOwnerSettings($postID) {
		if (empty($_POST) or !is_admin() or !isset($_POST['directorium_fields_check'])) return;
		if (wp_verify_nonce($_POST['directorium_owner_check'], 'directorium_owner_controls') === false) return;

		// Have any owner IDs been specified (else, ignore)
		if (!isset($_POST['addownerids'])) return;

		// Add new owners
		$ids = Data::parseCSVidList($_POST['addownerids']);
		foreach ($ids as $userID)
			Owners::addOwnership($userID, $postID);

		// Delete owners
		if (isset($_POST['removeowner']))
			Owners::removeOwnership(absint($_POST['removeowner']), $postID);
	}


	/**
	 * Adds and causes to be displayed useful messages within the listing
	 * editor.
	 *
	 * @param array $messages
	 * @return array
	 */
	public function editorMessages(array $messages) {
		// Get the current listing
		$listing = $this->getPost($this->getListingID());

		// If $listing is false then this is not a Listing
		if ($listing === false) return $messages;

		// Make all standard post update messages available
		$msgList = isset($messages['post']) ? (array) $messages['post'] : array();

		// Wrap all existing messages
		foreach ($msgList as &$notice) $notice = HTML::wrapInDiv($notice, 'post-update-msg');

		$msgList[self::MSG_PENDING_AMENDMENT] = HTML::wrapInDiv(
			sprintf(__('An amendment has been submitted and is waiting for your attention. <br/> <a href="%s">Review the amendment.</a>',
				'directorium'), self::getAmendedPostLink()), 'amendment-pending');

		$msgList[self::MSG_VIEWING_AMENDMENT] = HTML::wrapInDiv(
			sprintf(__('<strong>You are currently previewing an amendment.</strong> <br/> You can edit it, publish it or <a href="%s">revert to the currently published version</a>. To publish this version, click the <em>Publish</em> button.',
				'directorium'), self::getOriginalPostLink()), 'amendment-viewing');

		// Is a regular (WP) message pending?
		if (isset($_GET['message']) and isset($msgList[$_GET['message']]))
			$wpMsg = $_GET['message'];

		// Do we need to show a (directory/amendment) message?
		if ($this->amendmentIsBeingViewed()) $dirMsg = self::MSG_VIEWING_AMENDMENT;
		elseif ($listing->hasPendingAmendment()) $dirMsg = self::MSG_PENDING_AMENDMENT;

		// Merge messages is we need to show a regular message plus a directory amendment message
		if (isset($wpMsg) and isset($dirMsg))
			$msgList[$wpMsg] .= $msgList[$dirMsg];

		// Or adapt the message var if we only need to show an amendment message
		elseif (isset($dirMsg) and !isset($wpMsg))
			$_GET['message'] = $dirMsg;

		// Merge and return
		$messages[Listing::POST_TYPE] = $msgList;
		return $messages;
	}


	/**
	 * If the listing amendment is being viewed returns true.
	 *
	 * @return bool
	 */
	public function amendmentIsBeingViewed() {
		$listing = $this->getPost($this->getListingID());

		if (is_numeric($listing->id) and $GLOBALS['pagenow'] === 'post.php'
			and isset($_GET['loadalternative']) and $_GET['loadalternative'] === 'amendment')
			return true;

		return false;
	}


	protected function getAmendedPostLink() {
		$query = array('loadalternative' => 'amendment');
		$query = array_merge($_GET, $query);
		unset($query['message']);
		$path = $GLOBALS['pagenow'].'?'.http_build_query($query);
		return get_admin_url(null, $path);
	}


	protected function getOriginalPostLink() {
		$query = array_intersect_key($_GET, array_flip(array('post', 'action')));
		$path = $GLOBALS['pagenow'].'?'.http_build_query($query);
		return get_admin_url(null, $path);
	}


	public function getListingID() {
		if ($this->postID === null)
			$this->postID = (int) $GLOBALS['post']->ID;

		return $this->postID;
	}


	/**
	 * Optionally disable autosaves (post revisions) to avoid confusion, according
	 * to user preference.
	 *
	 * We test to see if it is a Listing post type (regular or amendment) and then
	 * dequeue the autosave script; there should be no impact on other posts.
	 */
	public function controlAutosaves() {
		global $post_type;

		switch ($post_type) {
			case Listing::POST_TYPE: case Listing::AMENDMENT_TYPE: break;
			default: return;
		}

		if (Core()->settings->get('general.disablePostRevisions'))
			wp_dequeue_script('autosave');
	}
}