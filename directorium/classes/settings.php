<?php
namespace Directorium;


class Settings {
	public $config = array();
	public $settings = array();

	protected $notices = array();
	protected $setCalled = false;



	public function __construct() {
		if (is_admin()) $this->settingsPage();
		$this->saveChanges();
	}


	protected function saveChanges() {
		if (isset($_POST['directoriumSettings']) and wp_verify_nonce($_POST['directoriumSettings'], 'directorySettings')) {
			// Ensure we have all expected fields (to account for checkbox fields which will not
			// be submitted when unchecked, for example)
			$expected = $this->getExpectedPostFields();
			$post = array_merge($expected, $_POST);

			foreach($post as $key => $value) {
				// Settings are prefixed __ check for that and knock it off the start
				if (strpos($key, '__') !== 0) continue;
				$key = substr($key, 2);

				// Check we have two parts reflecting the setting.path
				$setting = explode('_', $key);
				if (count($setting) !== 2) continue;

				// Update
				$this->set(implode('.', $setting), $value);
			}
			$this->save();
			$this->clear();
			$this->notices['info'][] = __('Settings have been updated', 'directorium');
		}
	}


	protected function settingsPage() {
		add_action('admin_menu', array($this, 'registerPage'));
	}


	/**
	 * Registers the settings UI page.
	 */
	public function registerPage() {
		$parent = 'edit.php?post_type='.Listing::POST_TYPE;
		$title = __('Settings', 'directorium');
		$capability = apply_filters('directorium_settings_capability', 'manage_options');

		$this->handle = add_submenu_page($parent, $title, $title, $capability, 'settings', array($this, 'controller'));
		add_action('admin_head-'.$this->handle, array($this, 'lineUpResources'));
	}


	/**
	 * Prep our stylesheet and JS behaviours.
	 */
	public function lineUpResources() {
		wp_enqueue_style('directoriumSettingsCSS', Core::$plugin->url.'assets/directory-settings.css');
		wp_enqueue_script('directoriumSettingsJS', Core::$plugin->url.'assets/directory-settings.js');
	}


	/**
	 * Render the importer page.
	 */
	public function controller() {
		View::write('frame', array(
			'action' => get_admin_url(null, 'edit.php?post_type='.Listing::POST_TYPE.'&page=settings'),
			'content' => new View('settings', array('settings' => $this)),
			'title' => __('Directory Settings', 'directorium'),
			'notices' => $this->notices
		));
	}


	public function renderAll($group) {
		$this->loadConfig($group);

		if (isset($this->config[$group])) {
			foreach ($this->config[$group] as $key => $item) {
				$action = 'directorium_print_setting_'.strtolower($group.'_'.$key);
				do_action($action, $item[0], $item[1]); // $data, $label
			}
		}
	}


	/**
	 * Returns an array listing all expected settings fields as key:values
	 * (the values being empty strings).
	 *
	 * @return array
	 */
	protected function getExpectedPostFields() {
		$expectedDefinitions = $this->returnArrayFile(Core::$plugin->dir.'/config/options.php');
		$expectedFields = array();

		foreach ($expectedDefinitions as $group)
			foreach ($group[0] as $key)
				$expectedFields[] = '__'.str_replace('.', '_', $key);

		return array_fill_keys($expectedFields, '');
	}


	/**
	 * Returns a setting value relating to a key in group.item format, such as
	 * "general.directorySlug".
	 *
	 * If optional param $print is true then the renderer/printer callback will
	 * also be called.
	 *
	 * @param $key
	 * @param $print = false
	 * @return mixed (null if not found)
	 */
	public function get($key, $print = true) {
		$parts = explode('.', $key);
		if (count($parts) !== 2) return null;

		// Load into settings cache if available, return null if not
		if (!isset($this->settings[$parts[0]])) $this->loadConfig($parts[0]);
		if (!isset($this->settings[$parts[0]][$parts[1]])) return null;

		// Filter
		$filter = strtolower('directorium_setting_'.str_replace('.', '_', $key));
		$setting = $this->settings[$parts[0]][$parts[1]];
		$setting = apply_filters($filter, $setting);

		// Optionally call the printer
		if ($print) {
			$action = strtolower('directorium_print_setting_'.str_replace('.', '_', $key));
			$data = array($key, $setting);
			$label = $this->config[$parts[0]][$parts[1]][1];
			do_action($action, $data, $label);
		}

		return $setting;
	}


	protected function loadConfig($group) {
		// Get base definitions (from the plugin's config files)
		$path = Core::$plugin->dir.'/config/'.$group.'.php';
		if (file_exists($path)) $config = $this->returnArrayFile($path);
		else $config = array();

		// Allow other settings to be registered for the same group
		$filter = 'directorium_group_setting_'.strtolower($group);
		$this->config[$group] = $config = apply_filters($filter, $config);

		// Set up validation callbacks/printer callbacks and derive list of raw settings
		$settings = $this->prepareSettings($group, $config);

		$dbsettings = (array) get_option('directoriumSetting'.ucfirst($group), array());
		$settings = array_merge($settings, $dbsettings);

		$this->settings[$group] = $settings;
	}


	protected function returnArrayFile($path) {
		$settings = $this; // Provide access to this object
		return (array) include $path;
	}


	protected function prepareSettings($group, array $config) {
		$settings = array();

		foreach ($config as $item => $params) {
			$settings[$item] = $params[0];

			// Validation callback?
			if (isset($params[2]) and $params[2] !== false)
				add_filter('directorium_update_setting_'.strtolower($group.'_'.$item), $params[2]);

			// Printer callback?
			if (isset($params[3]) and $params[3] !== false)
				add_action('directorium_print_setting_'.strtolower($group.'_'.$item), $params[3], 10, 2);
		}

		return $settings;
	}


	public function set($key, $value) {
		$setting = explode('.', $key);
		if (count($setting) !== 2) return false;

		$group = $setting[0];
		$key = $setting[1];

		// Validation/sanitization opportunity
		$filter = strtolower(implode('.', $setting));
		$value = apply_filters('directorium_update_setting_'.$filter, $value);

		$this->settings[$group][$key] = $value;
		$this->setCalled = true;
	}


	public function __destruct() {
		if ($this->setCalled) $this->save();
	}


	public function save() {
		foreach ($this->settings as $group => $keyValues) {
			$setting = 'directoriumSetting'.ucfirst($group);
			$dbsettings = (array) get_option($setting, array());
			$newsettings = array_merge($dbsettings, $keyValues);
			update_option($setting, $newsettings);
		}
		$this->setCalled = false;
	}


	protected function clear() {
		$this->config = array();
		$this->settings = array();
	}


	public function printSection($data, $label) {
		View::write('settings-section', array(
			'label' => $label,
			'settings' => $this,
			'keys' => $data
		));
	}

	public function printStringField($data, $label) {
		View::write('settings-field-string', array(
			'label' => $label,
			'key' => $data[0],
			'value' => $data[1]
		));
	}


	public function printCheckboxField($data, $label) {
		View::write('settings-field-checkbox', array(
			'label' => $label,
			'key' => $data[0],
			'value' => ($data[1] === '1') ? 1 : 0
		));
	}
}