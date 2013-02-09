<?php
namespace Directorium;
use Directorium\Helpers\View as View;


class Importer {
	public $handle = '';
	public $notices = array();

	protected $count = 0;
	protected $skipLine1 = false;
	protected $type = false;
	protected $fails = 0;
	protected $taxonomyCleanup = array();

	protected $listingMap = array(
		0 => 'Title',
		1 => 'Description',
		2 => 'Address1',
		3 => 'Address2',
		4 => 'City',
		5 => 'Region',
		6 => 'PostCode',
		7 => 'Country',
		8 => 'URL',
		9 => 'Email',
		10 => 'Phone',
		11 => 'word',
		12 => 'char',
		13 => 'image'
	);


	public function __construct() {
		add_action('init', array($this, 'handleImports'), 50); // after the custom taxonomies are registered
		add_action('init', array($this, 'cleanTaxonomyCache'), 40);
		add_action('admin_menu', array($this, 'registerPage'));
	}


	/**
	 * Test to see if a CSV file was uploaded for import; trigger the import
	 * process if so.
	 */
	public function handleImports() {
		if (isset($_POST['directoriumImport']) and wp_verify_nonce($_POST['directoriumImport'], 'importCSVFile') and isset($_FILES['csvfile'])) {
			$this->skipLine1 = (isset($_POST['ignoreline1']) and $_POST['ignoreline1'] === '1') ? true : false;
			$this->type = $_POST['type'];

			if ($_FILES['csvfile']['error'] === UPLOAD_ERR_OK) $this->parseUploadedCSV();
			else $this->notices['problem'][] = __('Failed to upload CSV file for import', 'directorium');
		}
	}


	/**
	 * Let's adopt Unix style line endings and break the file into an array of
	 * individual lines - then parse one by one.
	 */
	public function parseUploadedCSV() {
		// Try to get the CSV data and enforce consistent line endings
		$csv = file_get_contents($_FILES['csvfile']['tmp_name']);
		$csv = str_replace("\r", "\n", str_replace("\r\n", "\n", $csv));

		if ($csv === false or empty($csv)) {
			$this->notices['problem'][] = __('File was empty or could not be read', 'directorium');
			return;
		}

		// Break into individual lines and parse line by line
		$csv = explode("\n", $csv);
		foreach ($csv as $line) $this->importLine($line);

		// Assess success/failure rate to report back to the end-user
		$this->report();
	}


	/**
	 * Parse the line of CSV according to the specified type property. When the
	 * skipLine1 property is true (such as if the first row of CSV represents
	 * column headings) the initial line will not be parsed.
	 *
	 * @param $csv
	 */
	protected function importLine($csv) {
		$this->count++;
		if ($this->skipLine1 and $this->count === 1) return;

		switch ($this->type) {
			case 'businesstypes': $this->importTaxonomyData($csv, Listing::TAX_BUSINESS_TYPE); break;
			case 'geographies': $this->importTaxonomyData($csv, Listing::TAX_GEOGRAPHY); break;
			case 'listings': $this->importListing($csv); break;
		}

		// Record the IDs and taxonomy to be cleaned up in a transient. We'll give 15 minutes grace
		// for this to be used (manually) ... normally an ajax op should mean it's handled within a
		// few seconds
		if (count($this->taxonomyCleanup) > 0)
			set_transient('directoriumTaxonomyCleanup', $this->taxonomyCleanup, 60 * 15);
	}


	/**
	 * Tries to form a new Listing based on the supplied line of CSV data.
	 *
	 * @param $csv
	 */
	protected function importListing($csv) {
		// Get the individual fields and remove trailing/leading whitespace from each
		$fields = str_getcsv($csv);
		foreach ($fields as &$field) $field = trim($field);

		// Skip if we have nothing to work with
		if (count($fields) === 1 and empty($fields[0])) return;
		$properties = array();

		foreach ($this->listingMap as $index => $property)
			if (isset($fields[$index])) $properties[$property] = $fields[$index];

		$this->createListing($properties);
	}


	/**
	 * Builds the actual listing post.
	 *
	 * @param $properties
	 * @return bool
	 */
	protected function createListing($properties) {
		$listing = $this->createNewListingPost($properties);
		if ($listing === false) return;

		// Set the custom fields
		for ($i = 2; $i <= 10; $i++) {
			$field = $this->listingMap[$i];
			if (isset($properties[$field]))
				$listing->setField("directorium$field", $properties[$field]);
		}

		// Set the editorial limits
		for ($i = 11; $i <= 13; $i++) {
			$field = $this->listingMap[$i];
			if (isset($properties[$field]))
				$listing->setLimit($field, $properties[$field]);
		}
	}


	/**
	 * Creates a new listing post-type based on the $properties array, which
	 * should include a Title and Description key.
	 *
	 * @param $properties
	 * @return bool|int|\WP_Error
	 */
	protected function createNewListingPost(array $properties) {
		$post = wp_insert_post(array(
			'post_type' => Listing::POST_TYPE,
			'post_title' => $properties['Title'],
			'post_content' => isset($properties['Description']) ? $properties['Description'] : ''
		));

		if (is_int($post)) {
			$listing = new Listing($post);
			return $listing;
		}
		else {
			$this->notices['problem']['postcreation'] = __('One or more listings could not be created', 'directorium');
			$this->fails++;
			return false;
		}
	}


	/**
	 * Imports hierarchical taxonomy CSV data.
	 *
	 * @param string $csv
	 * @param string $taxonomy
	 */
	protected function importTaxonomyData($csv, $taxonomy) {
		list($parent, $child) = $this->interpretHierarchy($csv);
		if (empty($parent)) $term = $this->makeTerm($taxonomy, $child);
		else $term = $this->makeTerm($taxonomy, $child, $parent);
		if (is_int($term)) $this->taxonomyCleanup[$taxonomy][] = $term;
	}


	/**
	 * Takes what is expected to be a one or two column line of CSV. If there
	 * is only a single populated column or only one that is populated then that
	 * will be interpreted as the child.
	 *
	 * If two columns are present and populated, the first is interpreted as
	 * the parent and the second the child.
	 *
	 *  [0] => parent [maybe empty]
	 *  [1] => child [should not be empty]
	 *
	 * @param $csv
	 * @return array
	 */
	protected function interpretHierarchy($csv) {
		$columns = str_getcsv($csv);
		$parent = '';
		$child = '';
		$empty = 0;

		// Get rid of trailing/leading whitespace, assess emptiness
		foreach ($columns as &$column) {
			$column = trim($column);
			if (empty($column)) $empty++;
		}

		// Only 1 column populated?
		if (count($columns) >= 2 and $empty === 1) {
			if (empty($columns[0])) $child = $columns[1];
			else $child = $columns[0];
		}

		// Or if 2 columns are populated
		elseif (count($columns) >= 2 and $empty === 0) {
			$parent = $columns[0];
			$child = $columns[1];
		}

		// Or only 1 column passed in (non-empty)?
		elseif (count($columns) === 1 and $empty === 0) {
			$child = $columns[0];
		}

		return array($parent, $child);
	}


	/**
	 * Creates a new term in the specified taxonomy (if it does not already
	 * exist) and assigns it to the $parent category, if specified.
	 *
	 * @param $taxonomy
	 * @param $name
	 * @param null $parent
	 * @return mixed int|bool false
	 */
	protected function makeTerm($taxonomy, $name, $parent = null) {
		// Make/get the parent if specified
		if ($parent !== null) {
			$parentID = $this->makeTerm($taxonomy, $parent);
			if ($parentID === false) {
				$this->fails++;
				return;
			}
		}

		// Test if the term exists
		$term = get_term_by('name', $name, $taxonomy);

		// If it does not, try to create it
		if ($term === false) {
			if (isset($parentID)) $args = array('parent' => $parentID);
			else $args = array();

			$term = wp_insert_term($name, $taxonomy, $args);
		}
		// If it does exist and we have a parent then assign it to that parent
		if ($term !== false and isset($parentID)) {
			$term = (object) $term;
			$term = wp_update_term($term->term_id, $taxonomy, array('parent' => $parentID));
		}

		// Return the term ID or false
		if (is_array($term)) return $term['term_id']; // If just created
		elseif (is_object($term)) return $term->term_id; // If pre-existing
		else { // If not found/creation failed
			$this->fails++;
			return false;
		}
	}


	/**
	 * Handles taxonomy clean up requests (fired by the requestTaxonomyRefresh()
	 * method). It may be called either by ajax or "manually". In the case of ajax
	 * calls it will terminate script execution.
	 */
	public function cleanTaxonomyCache() {
		$fail = false;
		$query = array();
		$query = parse_str($_SERVER['QUERY_STRING'], $query);

		$this->taxonomyCleanup = get_transient('directoriumTaxonomyCleanup');
		if ($this->taxonomyCleanup === false) $fail = true;
		if (isset($query['check']) and wp_verify_nonce($query['check'], 'doTaxonomyCleanup') === false) $fail = true;

		if ($fail and isset($query['ajax'])) exit();
		elseif ($fail) return;

		foreach ($this->taxonomyCleanup as $taxonomy => $idList)
			clean_term_cache($idList, $taxonomy);

		$this->notices['success'][] = __('Cleanup tasks completed.', 'directorium');
		delete_transient('directoriumTaxonomyCleanup');

		if (isset($query['ajax'])) exit('ok');
		return;
	}


	/**
	 * Creates notices to feedback to the user pertaining to success or lack of
	 * during import.
	 */
	protected function report() {
		if ($this->count > 0) {
			$successfulImports = $this->count - $this->fails;
			$cleanupNeeded = (count($this->taxonomyCleanup) > 0) ? true : false;

			if ($successfulImports > 0 and !$cleanupNeeded)
				$this->notices['success']['imports'] =
					sprintf(__('%d items imported successfully!', 'directorium'), $successfulImports);

			if ($successfulImports > 0 and $cleanupNeeded)
				$this->notices['success']['imports'] =
					sprintf(__('%d items imported successfully! However, the system needs to perform some additional cleanup tasks.', 'directorium'), $successfulImports);

			if ($this->fails > 0)
				$this->notices['problem']['imports'] =
					sprintf(__('%d items failed to be imported.', 'directorium'), $this->fails);
		}
	}


	/**
	 * Registers the import UI page.
	 */
	public function registerPage() {
		$parent = 'edit.php?post_type='.Listing::POST_TYPE;
		$title = __('Importer', 'directorium');
		$capability = apply_filters('directorium_import_capability', 'import');

		$this->handle = add_submenu_page($parent, $title, $title, $capability, 'import', array($this, 'controller'));
		add_action('admin_head-'.$this->handle, array($this, 'lineUpResources'));
	}


	/**
	 * Prep our stylesheet and JS behaviours.
	 */
	public function lineUpResources() {
		wp_enqueue_style('directoriumImportCSS', Core::$plugin->url.'assets/directory-importer.css');
		wp_enqueue_script('directoriumImportJS', Core::$plugin->url.'assets/directory-importer.js');
	}


	/**
	 * Render the importer page.
	 */
	public function controller() {
		View::write('frame', array(
			'action' => get_admin_url(null, 'edit.php?post_type='.Listing::POST_TYPE.'&page=import'),
			'content' => new View('import-general',
				array('taxonomyCleanup' => $this->taxCleanupRequirement() )),
			'title' => __('Directory Import Tool', 'directorium'),
			'notices' => $this->notices
		));
	}


	/**
	 * Returns an object that can generate a link to take the end-user to the
	 * next phase (cleanup) of the taxonomy import operation, if required.
	 */
	protected function taxCleanupRequirement() {
		if (count($this->taxonomyCleanup) >= 1) {
			set_transient('directoriumCleanupNeeded', $this->taxonomyCleanup);
			return $this->taxonomyCleanup;
		}
		return false;
	}
}