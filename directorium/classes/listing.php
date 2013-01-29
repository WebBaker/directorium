<?php
namespace Directorium;


class Listing {
	const POST_TYPE = 'directorium_listing';
	const TAX_BUSINESS_TYPE = 'directorium_business_type';
	const TAX_GEOGRAPHY = 'directorium_geography';

	public $id;
	public $post;
	public $strippedPostContent = '';
	public $postAttachments = array();
	public $postTerms = array();


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
	 * If the listing is currently a draft then the post itListingAdmin will be amended
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
	 * (exist within ListingAdmin::$customFields) then they will be inserted using
	 * setField() ... others will be created as regular post custom fields.
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
		$postdata['post_type'] = Listing::POST_TYPE; // We only allow Listings to be created

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
		$postdata['post_type'] = Listing::POST_TYPE; // Disallow post type changes

		// Build a list of non-standard post fields (custom fields, basically)
		$customfields = $postdata;
		foreach ($this->post as $key => $name)
			if (isset($customfields[$key])) unset($customfields[$key]);

		wp_update_post($postdata); // Update the listing post itListingAdmin
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

		if (empty($keyList)) foreach (ListingAdmin::$customFields as $field)
			$keyList[] = $field[ListingAdmin::FIELD_NAME];

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


	/**
	 * Returns an array of Field objects (representing the existing custom fields).
	 *
	 * @return array
	 */
	public function getCustomFields() {
		$fieldGroups = array();

		foreach (ListingAdmin::$customFields as $definition) {
			$field = new Field($definition[0], $definition[1], $definition[2], null, $this->getField($definition[0]));
			$key = isset($definition[ListingAdmin::FIELD_GROUP]) ? $definition[ListingAdmin::FIELD_GROUP] : 'misc';
			$fieldGroups[$key][] = $field;
		}

		return $fieldGroups;
	}
}
