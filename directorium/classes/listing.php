<?php
namespace Directorium;
use Exception as Exception;
use WP_Query as WP_Query;


class Listing {
	const POST_TYPE = 'directorium_listing';
	const AMENDMENT_TYPE = 'directorium_amend'; // Shortened to fit in the current 20 char limit
	const TAX_BUSINESS_TYPE = 'directorium_business_type';
	const TAX_GEOGRAPHY = 'directorium_geography';

	public $id;
	public $post;
	public $strippedPostContent = '';
	public $postAttachments = array();
	public $postTerms = array();
	public $isAmendment = false;

	public $amendmentID;
	public $amendmentPost;

	public $originalID;
	public $originalPost;



	public function __construct($id = null) {
		if ($id !== null) $this->load($id);
		do_action('directorium_listing_initialized', $this);
	}


	protected function load($id) {
		$this->id = (int) $id;
		$this->post = get_post($this->id);

		// It's possible we'll end up with a null value if the post ID does not relate to a listing
		if (!is_a($this->post, 'WP_Post'))
			throw new Exception('Failed to initialize listing object. The wrong ID may have been provided.');

		$this->loadBothVersions();
	}


	/**
	 * Ensures we have access to both the published/original listing and also the
	 * amendment, if one exists.
	 */
	protected function loadBothVersions() {
		switch ($this->post->post_type) {
			case self::POST_TYPE:
				$this->originalID = $this->id;
				$this->originalPost = $this->post;
				$this->maybeLoadAmendment();
			break;
			case self::AMENDMENT_TYPE:
				$this->amendmentID = $this->id;
				$this->amendmentPost = $this->post;
				$this->isAmendment = true;
				$this->loadOriginal();
			break;
			default:
				throw new Exception('Unexpected post type: listing objects can only represent listing post types.');
			break;
		}
	}

	/**
	 * If there is a pending amendment for the post it will exist as a child post of type
	 * Listing::AMENDMENT_TYPE. This method loads that amendment into memory (if it exists).
	 */
	protected function maybeLoadAmendment() {
		// Clear the amendment properties first of all (in case this is a re-load)
		$this->amendmentID = $this->amendmentPost = null;
		$this->isAmendment = false;

		$amendment = new WP_Query(array(
			'post_parent' => $this->id,
			'post_type' => self::AMENDMENT_TYPE,
			'post_status' => array('pending', 'draft')
		));

		if ($amendment->have_posts()) {
			// Accessing the post directly to avoid further cleanup issues caused if the
			// WP_Query::the_post() is called at this point in the request
			$post = array_shift($amendment->posts);
			$this->amendmentID = $post->ID;
			$this->amendmentPost = $post;
		}

		wp_reset_query(); // Cleanup
	}


	/**
	 * When the object has been initialized using an amendment ID, this method can load in
	 * the published/original/parent post.
	 */
	protected function loadOriginal() {
		$original = new WP_Query(array(
			'p' => $this->amendmentPost->post_parent,
			'post_type' => self::POST_TYPE
		));

		if ($original->have_posts()) {
			// Accessing the post directly to avoid further cleanup issues caused if the
			// WP_Query::the_post() is called at this point in the request
			$post = array_shift($original->posts);
			$this->originalID = $post->ID;
			$this->originalPost = $post;
		}

		wp_reset_query(); // Cleanup
	}


	public function setLimit($type, $limit) {
		$limit = $this->processLimitString($limit);
		$type = ucfirst(strtolower($type));
		return update_post_meta($this->id, "_directoriumLimit$type", $limit);
	}


	protected function processLimitString($limit) {
		// Check for verbose "no limit" instructions
		$noLimitStr = __('no limit', 'directorium');
		if (stripos($limit, $noLimitStr) !== false) $limit = ListingAdmin::UNLIMITED;

		// Treat anything below zero as "no limit"
		$limit = (int) $limit;
		if ($limit < 0) $limit = ListingAdmin::UNLIMITED;

		return $limit;
	}


	public function getLimit($type) {
		$type = ucfirst(strtolower($type));
		$limit = get_post_meta($this->id, "_directoriumLimit$type", true);

		if (empty($limit) and $limit !== '0') $limit = ListingAdmin::UNLIMITED;
		return (int) $limit;
	}


	public function setField($key, $value) {
		return update_post_meta($this->id, "_$key", $value);
	}


	public function getField($key) {
		return get_post_meta($this->id, "_$key", true);
	}


	/**
	 * Creates an amendment post in which to hold any changes, prior to approval by a directory
	 * admin. Most post fields used with wp_update_post() can be used, others are assumed to be
	 * custom fields.
	 *
	 * Taxonomy amendments can be specified with a taxonomyTerms key, the value should be an array
	 * structured like so:
	 *
	 * [tax_name => [ term_id, term_id, ... ], ... ]
	 *
	 * @param array $postdata
	 * @return mixed (false if doing it wrong)
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

		$this->recordAmendmentRequest($postdata);
	}


	/**
	 * Creates a new listing. $postdata can contain any standard post fields (post_title,
	 * post_content etc) as well as any registered custom fields.
	 *
	 * @param array $postdata
	 * @param string $type Listing::POST_TYPE
	 * @return bool true on success or false
	 */
	public function create(array $postdata, $type = self::POST_TYPE) {
		if (isset($postdata['ID'])) unset($postdata['ID']); // The post ID cannot be dictated
		$postdata['post_type'] = $type; // We only allow Listings to be created

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


	/**
	 * Takes the original ("master") listing offline by setting it's post status to "draft".
	 * This only functions where the post status is at "publish".
	 */
	public function takeMasterOffline() {
		// Remember the current mode
		$returnToAmendment = $this->isAmendment;

		$this->switchToOriginal();
		$this->update(array('post_status' => 'draft'));

		// Restore the correct mode
		if ($returnToAmendment) $this->switchToAmendment();
	}


	protected function update(array $postdata) {
		$postdata['ID'] = $this->id; // ID will remain unchanged
		unset($postdata['post_type']); // Disallow post type changes

		// Build a list of non-standard post fields (custom fields, basically)
		$customfields = $postdata;
		foreach ($this->post as $key => $name)
			if (isset($customfields[$key])) unset($customfields[$key]);

		wp_update_post($postdata); // Update the listing post
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
	 * (That is, that it exists within ListingAdmin::$customFields.)
	 *
	 * @param $key
	 * @return bool
	 */
	public function isCustomListingField($key) {
		static $keyList = array();

		if (empty($keyList)) foreach (Core()->listingAdmin->customFields as $field)
			$keyList[] = $field[ListingAdmin::FIELD_NAME];

		return in_array($key, $keyList);
	}


	protected function recordAmendmentRequest($postdata) {
		// Prepare the postdata
		$postdata = array_merge((array) $this->post, $postdata, array(
			'post_parent' => $this->originalID,
			'post_status' => 'pending',
			'post_type' => self::AMENDMENT_TYPE
		));

		// Is this a new amendment?
		if (!isset($this->amendmentPost)) {
			unset($postdata['ID']);
			$amendmentID = wp_insert_post($postdata);
			$this->clonePostExtras($amendmentID);
		}
		else {
			$amendmentID = $postdata['ID'] = $this->amendmentID;
			wp_update_post($postdata);
		}

		// Reload (and explicitly switch to the amendment)
		$this->load($amendmentID);
		$this->switchToAmendment();

		$this->updateFields($postdata);

		// Optionally update assigned taxonomy terms
		if (isset($postdata['taxonomyTerms']))
			$this->updateTaxonomyTerms($amendmentID, $postdata['taxonomyTerms']);
	}


	/**
	 * Attempts to update assigned taxonomy terms for the specified post. Array
	 * $taxTerms is expected to be structured with the taxonomy as the key and an
	 * array of term IDs as the value:
	 *
	 * $taxTerms => [ TAX_NAME => [ 1, 2, 3 ... ], TAX_NAME_2 => [ 4, 5, ... ], ... ]
	 *
	 * @param $postID
	 * @param array $taxTerms
	 */
	protected function updateTaxonomyTerms($postID, array $taxTerms) {
		foreach ($taxTerms as $taxonomy => $termIDs)
	        wp_set_post_terms($postID, $termIDs, $taxonomy, false);
	}


	/**
	 * Clones the contents of the listing's primary or published post, so that all
	 * meta data, attachments and taxonomy terms transfer to the clone post.
	 *
	 * @todo consider breaking out the Clone functionality into a new class.
	 *
	 * @param $cloneID
	 */
	protected function clonePostExtras($cloneID) {
	    $this->cloneMeta($cloneID);
		$this->cloneAttachments($cloneID);
		$this->cloneTerms($cloneID);
		$this->cloneOwnership($cloneID);
	}


	/**
	 * Copies any attachment entries in the post table and assigns to the clone
	 * (amendment) post.
	 *
	 * @param $cloneID
	 * @param $sourceID = null
	 */
	protected function cloneAttachments($cloneID, $sourceID = null) {
		$id = ($sourceID === null) ? $this->id : absint($sourceID);

		$attachments = new WP_Query(array(
			'post_parent' => $id,
			'post_type' => 'attachment',
			'post_status' => 'any'
		));

		if ($attachments->have_posts()) {
			while (count($attachments->posts) > 0) {
				// Accessing the post directly to avoid further cleanup issues caused if the
				// WP_Query::the_post() is called at this point in the request
				$attachment = (array) array_shift($attachments->posts);

				// Unset the ID (save a copy first of all) and change the parent then re-insert
				$originalAttachmentID = $attachment['ID'];
				unset($attachment['ID']);
				$attachment['post_parent'] = $cloneID;
				$attachmentID = wp_insert_post($attachment);

				// Copy across attached meta for the attachment
				$this->cloneMeta($attachmentID, $originalAttachmentID);
			}
		}

		wp_reset_query(); // Cleanup
	}


	/**
	 * Copies the listing meta data to the clone (amendment) post. If no $sourceID is
	 * specified it is assumed that the meta data must come from the parent listing.
	 *
	 * @param $cloneID
	 * @param $sourceID = null
	 */
	protected function cloneMeta($cloneID, $sourceID = null) {
		$id = ($sourceID === null) ? $this->id : absint($sourceID);
		$meta = get_post_custom($id);

		foreach ($meta as $key => $data)
			foreach ($data as $value)
				add_post_meta($cloneID, $key, Data::makeUnserialized($value));
	}


	/**
	 * Takes terms from the source object and applies them to the clone object.
	 *
	 * @param $cloneID
	 * @param null $sourceID
	 */
	protected function cloneTerms($cloneID, $sourceID = null) {
		$id = ($sourceID === null) ? $this->id : absint($sourceID);
		$taxonomies = get_object_taxonomies(get_post($id));

		// Build list of terms by taxonomy
		$termList = array();
		foreach (wp_get_object_terms($id, $taxonomies) as $term)
			$termList[$term->taxonomy][] = absint($term->term_id);

		// Set the terms by taxonomy
		foreach ($termList as $taxonomy => $termIDs)
			wp_set_object_terms($cloneID, $termIDs, $taxonomy);
	}


	/**
	 * Takes the assigned owners of the source object and gives them ownership of the
	 * clone object also.
	 *
	 * @param $cloneID
	 * @param null $sourceID
	 */
	protected function cloneOwnership($cloneID, $sourceID = null) {
		$id = ($sourceID === null) ? $this->id : absint($sourceID);

		// Get source ownership and apply to the clone object
		$owners = Owners::whoOwnsThis($id);
		foreach ($owners as $user) Owners::addOwnership($user, $cloneID);
	}


	/**
	 * Switches the Listing into amendment mode, so that all of its fields are populated
	 * with it's amendment equivalents.
	 *
	 * @return bool
	 */
	public function switchToAmendment() {
		if (!$this->hasPendingAmendment()) return false; // No amendment data
		if ($this->isAmendment) return false; // Already in amendment mode

		$this->originalID = $this->id;
		$this->originalPost = clone $this->post;
		$this->id = $this->amendmentID;
		$this->post = clone $this->amendmentPost;

		$this->isAmendment = true;
		return true;
	}


	/**
	 * Switches the Listing back into its live/original mode (after having been placed in
	 * amendment mode).
	 *
	 * @return bool
	 */
	public function switchToOriginal() {
		if (!$this->isAmendment) return false; // Not in amendment mode

		$this->id = $this->originalID;
		$this->post = clone $this->originalPost;

		$this->isAmendment = false;
		return true;
	}


	/**
	 * Indicates if the master/original is online (published).
	 *
	 * @return bool
	 */
	public function originalIsOnline() {
		return ($this->originalPost->post_status === 'publish');
	}


	/**
	 * Returns true if an amendment has been submitted and requires attention,
	 * else returns false.
	 *
	 * @return bool
	 */
	public function hasPendingAmendment() {
		return isset($this->amendmentPost) ? true : false;
	}


	public function getAmendmentData() {
		return isset($this->amendmentPost) ? $this->amendmentPost : false;
	}


	/**
	 * Reassigns any children (ie, attachments) of the amendment to the original listing post.
	 */
	public function transferAttachmentsToOriginal() {
		$this->switchToAmendment();
		$attachments = $this->getPostAttachments();

		foreach ($attachments as $attachedPost) {
			$post = (array) $attachedPost;
			$post['post_parent'] = $this->originalID;
			wp_update_post($post);
		}
	}


	/**
	 * Destroys the amendment post (if it exists) and all associated data.
	 *
	 * This is handled using wp_delete_post(), however before that is called the relationship with the
	 * parent post (the published listing) is zeroed out, to avoid a problem with grandchildren (in this
	 * case, amendment attachments) being promoted to children (so they are attached to the published
	 * listing, often resulting in unwanted duplicates.
	 *
	 * @see http://codingwith.wordpress.com/2013/02/02/post-hierarchies-and-deleted-children/
	 *
	 * @return bool
	 */
	public function killAmendment() {
		if (!$this->hasPendingAmendment()) return false;

		// Break the parent:child relationship
		wp_update_post(array(
			'ID' => $this->amendmentID,
			'post_parent' => 0
		));

		// Now delete the amendment
		$deleteOperation = wp_delete_post($this->amendmentID) === false ? false : true;

		// Now reload the listing and return the result of the delete operation
		$this->load($this->originalID);
		return $deleteOperation;
	}


	/**
	 * Attaches an image where $fileIndex refers to the related entry in the $_FILES
	 * superglobal.
	 *
	 * @param $fileIndex
	 * @param $alt
	 * @param $title
	 * @param $caption
	 */
	public function attachImage($fileIndex) {
		if (!isset($_FILES[$fileIndex]) or $_FILES[$fileIndex]['error'] !== UPLOAD_ERR_OK) return false;
		require_once(ABSPATH.'wp-admin/includes/admin.php');
		require_once(ABSPATH.'wp-admin/includes/media.php');
		\media_handle_upload($fileIndex, $this->id);
	}


	public function removeImageAttachments(array $attIDs) {
		$currentlyAttached = $this->getAttachedImages();
		$currentAttachments = array();

		// Build a list of currently attached *IDs* only
		foreach ($currentlyAttached as $attachment)
			$currentAttachments[] = $attachment->ID;

		foreach ($attIDs as $id) {
			// Confirm each attachment (to be removed) belongs to this post first of all
			if (!in_array($id, $currentAttachments)) continue;

			// Remove
			wp_delete_attachment($id, true);
		}
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


	/**
	 * Returns an array of Field objects (representing the existing custom fields).
	 *
	 * @return array
	 */
	public function getCustomFields() {
		$fieldGroups = array();

		foreach (Core()->listingAdmin->customFields as $definition) {
			$field = new Field($definition[0], $definition[1], $definition[2], null, $this->getField($definition[0]));
			$key = isset($definition[ListingAdmin::FIELD_GROUP]) ? $definition[ListingAdmin::FIELD_GROUP] : 'misc';
			$fieldGroups[$key][] = $field;
		}

		return $fieldGroups;
	}
}
