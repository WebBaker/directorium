<?php
namespace Directorium;


class Settings {
	protected $notices = array();
	protected $settings = array();



	public function __construct() {
		if (is_admin()) $this->settingsPage();
		$this->saveChanges();
	}


	protected function saveChanges() {
		if (isset($_POST['directoriumSettings']) and wp_verify_nonce($_POST['directoriumSettings'], 'directorySettings')) {
			foreach($_POST as $key => $value) {
				// Settings are prefixed __ check for that and knock it off the start
				if (strpos($key, '__') !== 0) continue;
				$key = substr($key, 2);

				// Check we have two parts reflecting the setting.path
				$setting = explode('_', $key);
				if (count($setting) !== 2) continue;

				// Update
				$this->set(implode('.', $setting), $value);
			}
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
			'content' => new View('settings-general', array('settings' => $this)),
			'title' => __('Directory Settings', 'directorium'),
			'notices' => $this->notices
		));
	}


	/**
	 * Returns a setting value relating to a key in group.item format, such as
	 * "general.directorySlug".
	 *
	 * @param $key
	 * @return mixed (null if not found)
	 */
	public function get($key) {
		$parts = explode('.', $key);
		if (count($parts) !== 2) return null;

		// Load into settings cache if available, return null if not
		if (!isset($this->settings[$parts[0]])) $this->loadConfig($parts[0]);
		if (!isset($this->settings[$parts[0]][$parts[1]])) return null;

		// Filter and return
		$filter = strtolower('directorium_setting_'.str_replace('.', '_', $key));
		$setting = $this->settings[$parts[0]][$parts[1]];
		return apply_filters($filter, $setting);
	}


	protected function loadConfig($group) {
		$path = Core::$plugin->dir.'/config/'.$group.'.php';
		if (file_exists($path)) $settings = $this->returnArrayFile($path);
		else $settings = array();

		$dbsettings = (array) get_option('directoriumSetting'.ucfirst($group), array());
		$settings = array_merge($settings, $dbsettings);

		$this->settings[$group] = $settings;
	}


	protected function returnArrayFile($path) {
		return (array) include $path;
	}


	public function set($key, $value) {
		$setting = explode('.', $key);
		if (count($setting) !== 2) return false;

		$group = $setting[0];
		$key = $setting[1];
		$setting = 'directoriumSetting'.ucfirst($group);

		$dbsettings = (array) get_option($setting, array());
		$dbsettings[$key] = $value;

		return update_option($setting, $dbsettings);
	}
}