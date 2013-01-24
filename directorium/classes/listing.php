<?php
namespace Directorium;


class Listing {
	const CUSTOM_FIELDS = 'directorium_custom_fields';
	const EDITORIAL_CONTROLS = 'directorium_editorial_meta';
	const MEDIA_CONTROLS = 'directorium_media_meta';
	const OWNER_CONTROLS = 'directorium_owner_meta';
	const IMG_PREVIEW_SIZE = 'directorium_preview_thumb';
	const POST_TYPE = 'directorium_listing';
	const TAX_BUSINESS_TYPE = 'directorium_business_type';
	const TAX_GEOGRAPHY = 'directorium_geography';
	const FIELD_NAME = 0;
	const FIELD_GROUP = 3;
	const MSG_PENDING_AMENDMENT = 100;
	const MSG_VIEWING_AMENDMENT = 110;
	const UNLIMITED = -1;

	/**
	 * Container to store and retrieve Listing objects, avoiding the need to
	 * instantiate new objects unnecessarily.
	 *
	 * @var array
	 */
	protected static $postInstances = array();
	protected static $postID = null;

	/**
	 * Directory specific fields.
	 *
	 * Each field is described as an array of the pattern [name, type, label,
	 * group] where the group is primarily a means of grouping like fields in
	 * the admin environment.
	 *
	 * @var array
	 */
	public static $customFields = array(
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


	/**
	 * Returns the Listing object relating to the specified post ID.
	 *
	 * Allows for reuse of objects instead of generating multiple Listings relating
	 * to the same post.
	 *
	 * @param $id
	 * @return Listing
	 */
	public static function getPost($id) {
		$id = absint($id);
		if (isset(self::$postInstances[$id])) return self::$postInstances[$id];
		else {
			$class = __CLASS__;
			self::$postInstances[$id] = new $class($id);
			return self::$postInstances[$id];
		}
	}


	public static function register() {
		add_action('init', array(__CLASS__, 'registerTaxonomies'), 20);
		add_action('init', array(__CLASS__, 'registerPostType'), 30);
		self::registerPreviewImageSize();

		// Enqueue styles for both existing and new posts
		add_action('admin_print_styles-post.php', array(__CLASS__, 'editorStyles'));
		add_action('admin_print_styles-post-new.php', array(__CLASS__, 'editorStyles'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'editorScripts'));
	}


	public static function registerTaxonomies() {
		register_taxonomy(self::TAX_BUSINESS_TYPE, null, self::getBusinessTypeDefinition());
		register_taxonomy(self::TAX_GEOGRAPHY, null, self::getGeographyDefinition());
	}


	public static function getGeographyDefinition() {
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


	public static function getBusinessTypeDefinition() {
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


	public static function registerPostType() {
		register_post_type(self::POST_TYPE, self::getTypeDefinition());
		add_action('save_post', array(__CLASS__, 'saveCustomFields'));
		add_action('save_post', array(__CLASS__, 'saveEditorialSettings'));
		add_action('save_post', array(__CLASS__, 'saveOwnerSettings'));
		add_action('save_post', array(__CLASS__, 'saveMediaChanges'));
		add_filter('post_updated_messages', array(__CLASS__, 'editorMessages'));
	}


	public static function getTypeDefinition() {
		return array(
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
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'rewrite' =>  array(
				'slug' => _x('listing', 'slug', 'directorium')),
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => null,
			'register_meta_box_cb' => array(
				__CLASS__, 'metaboxes'),
			'supports' => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'comments'),
			'taxonomies' => array(
				self::TAX_BUSINESS_TYPE,
				self::TAX_GEOGRAPHY,
				'post_tag'));
	}


	public static function registerPreviewImageSize() {
		add_image_size(self::IMG_PREVIEW_SIZE, 112, 112);
	}


	public static function editorStyles() {
		wp_enqueue_style('directoriumEditor', Core::$plugin->url.'assets/listings-editor.css');
	}


	/**
	 * Sets up the editor script and text translations for that script.
	 */
	public static function editorScripts() {
		Translatables::add(array(
			'confirmAmendment' => __("Are you sure you have checked everything including address fields and images?\n\n"
				.'Continuing means you are accepting this version of the listing. If you are unsure, cancel this '
				.'operation and check everything again!', 'directorium')
		));
		wp_enqueue_script('directoriumEditor', Core::$plugin->url.'assets/listings-editor.js', array('jquery'));
	}


	public static function metaboxes() {
		add_meta_box(self::CUSTOM_FIELDS,
			__('Directory Fields', 'directorium'),
			array(__CLASS__, 'customFields'),
			self::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::EDITORIAL_CONTROLS,
			__('Editorial Controls', 'directorium'),
		    array(__CLASS__, 'editorialControls'),
			self::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::MEDIA_CONTROLS,
			__('Attached Media', 'directorium'),
			array(__CLASS__, 'mediaControls'),
			self::POST_TYPE,
			'advanced'
		);
		add_meta_box(self::OWNER_CONTROLS,
			__('Listing Ownership', 'directorium'),
			array(__CLASS__, 'ownerControls'),
			self::POST_TYPE,
			'side'
		);
	}


	/**
	 * Displays the custom fields meta box on the Listings editor page.
	 */
	public static function customFields() {
		$listing = self::getPost(self::getListingID());
		$fieldGroups = array();

		foreach (self::$customFields as $definition) {
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
	public static function editorialControls() {
		$listing = self::getPost(self::getListingID());
		View::write('editorial-controls', array('listing' => $listing));
	}


	public static function saveCustomFields() {
		if (empty($_POST) or !is_admin() or !isset($_POST['directorium_fields_check'])) return;
		if (wp_verify_nonce($_POST['directorium_fields_check'], 'directorium_custom_fields') === false) return;

		$listing = new Listing($GLOBALS['post']->ID);

		foreach (self::$customFields as $field)
			if (array_key_exists($field[0], $_POST))
				$listing->setField($field[0], $_POST[$field[0]]);
	}


	public static function saveEditorialSettings() {
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


	public static function mediaControls() {
		$listing = self::getPost(self::getListingID());
		View::write('media-controls', array('listing' => $listing));
	}


	public static function saveMediaChanges($postID) {
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
	protected static function detachMedia($mediaID, $nature = 'detach') {
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


	public static function ownerControls() {
		$listing = self::getPost(self::getListingID());
		$owners = Owners::whoOwnsThis($listing->id);
		$userAdminLink = get_admin_url(null, 'users.php');
		View::write('owner-controls', array('listing' => $listing, 'owners' => $owners));
	}


	public static function saveOwnerSettings($postID) {
		if (empty($_POST) or !is_admin() or !isset($_POST['directorium_fields_check'])) return;
		if (wp_verify_nonce($_POST['directorium_owner_check'], 'directorium_owner_controls') === false) return;

		// Add new owners
		$ids = Utility::parseCSVidList($_POST['addownerids']);
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
	public static function editorMessages(array $messages) {
		$messages = array_merge($messages, self::listingEditorMessages());
		$listing = self::getPost(self::getListingID());

		// Make it clear to the user if they are viewed the amended text/fields
		if ($listing->amendmentIsBeingViewed())
			$_GET['message'] = self::MSG_VIEWING_AMENDMENT;

		// Inform the user if an amendment is pending (unless another message is waiting)
		elseif ($listing->hasPendingAmendment() and !isset($_GET['message']))
			$_GET['message'] = self::MSG_PENDING_AMENDMENT;

		return $messages;
	}


	protected static function listingEditorMessages() {
		$messages[self::POST_TYPE] = array(
			self::MSG_PENDING_AMENDMENT => HTML::wrapInDiv(
				sprintf(__('An amendment has been submitted and is waiting for your attention. <br/> <a href="%s">Review the amendment.</a>',
					'directorium'), self::getAmendedPostLink()), 'amendment-pending'),
			self::MSG_VIEWING_AMENDMENT => HTML::wrapInDiv(
				sprintf(__('<strong>You are currently previewing an amendment.</strong> <br/> You can edit it, publish it or <a href="%s">revert to the currently published version</a>. To publish this version, click the update button.',
					'directorium'), self::getOriginalPostLink()), 'amendment-viewing')
		);

		return $messages;
	}


	protected static function getAmendedPostLink() {
		$query = array('loadalternative' => 'amendment');
		$query = array_merge($_GET, $query);
		$path = $GLOBALS['pagenow'].'?'.http_build_query($query);
		return get_admin_url(null, $path);
	}


	protected static function getOriginalPostLink() {
		$query = array_intersect_key($_GET, array_flip(array('post', 'action')));
		$path = $GLOBALS['pagenow'].'?'.http_build_query($query);
		return get_admin_url(null, $path);
	}


	public static function getListingID() {
		if (self::$postID === null)
			self::$postID = (int) $GLOBALS['post']->ID;

		return self::$postID;
	}


	public function __construct($id = null) {
		if ($id !== null) $this->load($id);
		do_action('directorium_listing_initialized', $this);
	}


	protected function load($id) {
		$this->id = (int) $id;
		$this->post = get_post($this->id);
	}


	public function setLimit($type, $limit) {
		$limit = $this->processLimitString($limit);
		$type = ucfirst(strtolower($type));
		return update_post_meta($this->id, "_directoriumLimit$type", $limit);
	}


	protected function processLimitString($limit) {
		// Check for verbose "no limit" instructions
		$noLimitStr = __('no limit', 'directorium');
		if (stripos($limit, $noLimitStr) !== false) $limit = self::UNLIMITED;

		// Treat anything below zero as "no limit"
		$limit = (int) $limit;
		if ($limit < 0) $limit = self::UNLIMITED;

		return $limit;
	}


	public function getLimit($type) {
		$type = ucfirst(strtolower($type));
		$limit = get_post_meta($this->id, "_directoriumLimit$type", true);

		if (empty($limit) and $limit !== '0') $limit = self::UNLIMITED;
		return (int) $limit;
	}


	public function setField($key, $value) {
		return update_post_meta($this->id, "_$key", $value);
	}


	public function getField($key) {
		return get_post_meta($this->id, "_$key", true);
	}


	/**
	 * If the listing is currently a draft then the post itself will be amended
	 * to reflect the data passed in.
	 *
	 * If the listing is live the data will be saved as meta data, and a flag
	 * set to indicate that a revision has been submitted. The idea is to
	 * facilitate a workflow where listees can submit revisions and the
	 * directory operator can moderate and sanitize them as required.
	 *
	 * $postdata is expected to be an array containing keys matching any post
	 * table columns where a change is required.
	 *
	 * Optionally, further custom fields can be passed. If they are "known"
	 * (exist within self::$customFields) then they will be inserted using
	 * setField() ... others will be created as regular post custom fields.
	 *
	 * @param array $postdata
	 */
	public function safeAmendment(array $postdata) {
		// A listing must already have been loaded or this will fail
		if (!isset($this->id)) {
			_doing_it_wrong('Directorium\\Listing::safeAmendment()', 'A listing must have been successfully loaded '
				.'before you can amend it.', '0.1.0');
			return false;
		}
		if (!is_array($postdata)) return;
		$postdata = apply_filters('directoriumSanitizeAmendmentData', $postdata);

		if ($this->post->post_status === 'draft') {
			$postdata['post_status'] = 'draft'; // Post status should not alter
			$this->update($postdata);
		}

		else $this->recordAmendmentRequest($postdata);
	}


	/**
	 * Creates a new listing. $postdata can contain any standard post fields (post_title,
	 * post_content etc) as well as any registered custom fields.
	 *
	 * @param array $postdata
	 * @return bool true on success or false
	 */
	public function create(array $postdata) {
		if (isset($postdata['ID'])) unset($postdata['ID']); // The post ID cannot be dictated
		$postdata['post_type'] = self::POST_TYPE; // We only allow Listings to be created

		// Try to insert the post - return false on failure and load on success
		$newID = wp_insert_post($postdata);
		if (!is_int($newID) or $newID < 1) return false;
		else $this->load($newID);

		// Build a list of non-standard post fields (custom fields, basically)
		$customfields = $postdata;
		foreach ($this->post as $key => $name)
			if (isset($customfields[$key])) unset($customfields[$key]);

		// Update the custom fields and return true
		$this->updateFields($customfields);
		return true;
	}


	protected function update(array $postdata) {
		$postdata['ID'] = $this->id; // ID will remain unchanged
		$postdata['post_type'] = self::POST_TYPE; // Disallow post type changes

		// Build a list of non-standard post fields (custom fields, basically)
		$customfields = $postdata;
		foreach ($this->post as $key => $name)
			if (isset($customfields[$key])) unset($customfields[$key]);

		wp_update_post($postdata); // Update the listing post itself
		$this->updateFields($customfields); // Update any supplied custom fields
	}


	protected function updateFields(array $fields) {
		$wpFields = array();

		// Update directory fields, building a list of any unregistered fields
		foreach ($fields as $key => $value)
			if (!$this->isCustomListingField($key)) $wpFields[$key] = $value;
			else $this->setField($key, $value);

		// Lets treat the unregistered fields as regular WP custom fields
		foreach ($wpFields as $key => $value)
			update_post_meta($this->id, $key, $value);
	}


	/**
	 * Checks to see if the specified key is registered as a custom directory
	 * field.
	 *
	 * (That is, that it exists within self::$customFields.)
	 *
	 * @param $key
	 * @return bool
	 */
	public function isCustomListingField($key) {
		static $keyList = array();

		if (empty($keyList)) foreach (self::$customFields as $field)
			$keyList[] = $field[self::FIELD_NAME];

		return in_array($key, $keyList);
	}


	protected function recordAmendmentRequest($postdata) {
		update_post_meta($this->id, '_directoriumAmendment', $postdata);
	}


	/**
	 * Returns true if an amendment has been submitted and requires attention,
	 * else returns false.
	 *
	 * @return bool
	 */
	public function hasPendingAmendment() {
		$amendment = get_post_meta($this->id, '_directoriumAmendment', true);
		return empty($amendment) ? false : true;
	}


	/**
	 * If the listing amendment is being viewed returns true.
	 *
	 * @return bool
	 */
	public function amendmentIsBeingViewed() {
		if (is_numeric($this->id) and $GLOBALS['pagenow'] === 'post.php'
			and isset($_GET['loadalternative']) and $_GET['loadalternative'] === 'amendment')
				return true;

		return false;
	}


	public function getAmendmentData() {
		return get_post_meta($this->id, '_directoriumAmendment', true);
	}


	/**
	 * Returns the number of words found in the content. Tags and content within element attributes
	 * are discarded.
	 *
	 * @return int
	 */
	public function getWordCount() {
		$content = $this->getStrippedContent();
		$words = explode(' ', $content);
		return (int) count($words);
	}


	public function getCharacterCount() {
		$content = $this->getStrippedContent(true);
		return (int) strlen($content);
	}


	public function getImageCount() {
		return (int) count($this->getAttachedImages());
	}


	public function getAttachedImages() {
		$attachments = $this->getPostAttachments();
		$images = array();

		foreach ($attachments as $att)
			if (strpos($att->post_mime_type, 'image') !== false)
				$images[] = $att;

		return $images;
	}


	public function getVideoCount() {
		return (int) count($this->getAttachedVideos());
	}


	public function getAttachedVideos() {
		$attachments = $this->getPostAttachments();
		$videos = array();

		foreach ($attachments as $att)
			if (strpos($att->post_mime_type, 'directory/movie') !== false)
				$videos[] = $att;

		return $videos;
	}

	public function getPostAttachments() {
		// Ensure a post has been loaded
		if (!isset($this->post) or !is_a($this->post, 'WP_Post')) return '';

		// If this has already run return the cached content
		if (!empty($this->postAttachments)) return $this->postAttachments;

		$atts = get_children(array('post_type' => 'attachment', 'post_parent' => $this->id));
		$this->postAttachments = $atts;
		return $this->postAttachments;
	}


	/**
	 * Returns a copy of the current post content, devoid of markup (useful for word and
	 * character counts).
	 *
	 * @param bool $retainWhitespace = false
	 * @return string
	 */
	public function getStrippedContent($retainWhitespace = false) {
		// Ensure a post has been loaded
		if (!isset($this->post) or !is_a($this->post, 'WP_Post')) return '';

		// If this has already run return the cached content
		if (!empty($this->strippedPostContent)) return $this->strippedPostContent;

		// Strip down
		$content = $this->post->post_content;
		$content = strip_tags($content);

		if (!$retainWhitespace) {
			$content = str_replace(array('  ', "\n", "\r"), ' ', $content);
			while (strpos($content, '  ') !== false)
				$content = str_replace('  ', ' ', $content);
		}

		$this->strippedPostContent = $content;
		return $this->strippedPostContent;
	}


	/**
	 * Returns the number of applied taxonomy terms for the current listing.
	 *
	 * @param $taxonomy
	 * @return int
	 */
	public function getTaxonomyCount($taxonomy) {
		$terms = $this->getTaxonomyTerms($taxonomy);
		return (int) count($terms);
	}


	/**
	 * Returns an array of all the taxonomy terms (must be a hierarchical taxonomy such
	 * as geography/business type) and annotates by assigning true or false if the term
	 * is in use.
	 *
	 * @param $taxonomy
	 * @return array
	 */
	public function annotatedTaxonomyList($taxonomy) {
		$this->getTaxonomyTerms($taxonomy);
		$allTerms = get_terms($taxonomy, array('hide_empty' => false));

		// Build a list of all terms from this taxonomy *applied* to this listing
		$applied = array();
		if (isset($this->postTerms[$taxonomy]))
			foreach ($this->postTerms[$taxonomy] as $term)
				$applied[] = $term->term_id;

		// Now restructure $allTerms so that each array key === term_id
		$orderedTerms = array();
		foreach ($allTerms as $termObject)
			$orderedTerms[$termObject->term_id] = $termObject;

		// Lets create a new array of terms with children nested under parents
		$terms = $orderedTerms;
		while (list($id, $individualTerm) = each($orderedTerms)) {
			// Has this term been applied to this listing?
			$terms[$id]->in_use = in_array($id, $applied);

			// Make sure each term has a children property as an empty array
			if (!isset($terms[$id]->children)) $terms[$id]->children = array();
			$parent = (int) $individualTerm->parent;

			// Does the current term have a parent?
			if ($parent > 0) {
				// Second opportunity to make sure it has a chilren property
				if (!isset($terms[$parent]->children)) $terms[$parent]->children = array();

				$terms[$parent]->children[$id] = $individualTerm; // Reposition
				unset($terms[$id]); // Clean up
			}
		}

		return $terms;
	}


	/**
	 * Returns a list of terms applied to this listing from the specified taxonomy.
	 *
	 * @param $taxonomy
	 * @return array
	 */
	public function getTaxonomyTerms($taxonomy) {
		// Ensure a post has been loaded
		if (!isset($this->post) or !is_a($this->post, 'WP_Post')) return array();

		// If this has already run return the cached content
		if (isset($this->postTerms[$taxonomy])) return (array) $this->postTerms[$taxonomy];

		$this->postTerms[$taxonomy] = (array) wp_get_post_terms($this->id, $taxonomy);
		return $this->postTerms[$taxonomy];
	}
}
