<?php wp_nonce_field('directorySettings', 'directoriumSettings'); ?>

<h4><?php _e('Directory Pages', 'directorium') ?></h4>

<div id="settings-actions">
	<p> <?php _e('Don&#146;t forget to save any changes that you have made!', 'directorium') ?> </p>
	<input type="submit" name="submit" id="savesettings" value="<?php esc_attr_e('Save Settings', 'directorium') ?>" class="button-secondary" />
</div>

<ul id="frontpages" class="settingslist">
	<li>
		<div> <label for="directoryslug"><?php _e('Directory slug', 'directorium') ?></label> </div>
		<div> <input type="text" name="__general_directorySlug" id="directoryslug" value="<?php esc_attr_e($settings->get('general.directorySlug')) ?>" /> </div>
		<div> </div>
	</li>

	<li>
		<div> <label for="geographyslug"><?php _e('Geography slug', 'directorium') ?></label> </div>
		<div> <input type="text" name="__general_geographySlug" id="geographyslug" value="<?php esc_attr_e($settings->get('general.geographySlug')) ?>" /> </div>
		<div> </div>
	</li>

	<li>
		<div> <label for="btypesslug"><?php _e('Business types slug', 'directorium') ?></label> </div>
		<div> <input type="text" name="__general_btypesSlug" id="btypesslug" value="<?php esc_attr_e($settings->get('general.btypesSlug')) ?>" /> </div>
		<div> </div>
	</li>

	<li>
		<div> <label for="ownerslistpage"><?php _e('Owner&#146;s admin list', 'directorium') ?></label> </div>
		<div> <input type="text" name="__general_listPage" id="ownerslistpage" value="<?php esc_attr_e($settings->get('general.listPage')) ?>" /> </div>
		<div> </div>
	</li>

	<li>
		<div> <label for="editorpage"><?php _e('Listing editor page', 'directorium') ?></label> </div>
		<div> <input type="text" name="__general_editorPage" id="editorpage" value="<?php esc_attr_e($settings->get('general.editorPage')) ?>" /> </div>
		<div> </div>
	</li>
</ul>