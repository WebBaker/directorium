<?php wp_nonce_field('directorySettings', 'directoriumSettings'); ?>

<div id="settings-actions">
	<p> <?php _e('Don&#146;t forget to save any changes that you have made!', 'directorium') ?> </p>
	<input type="submit" name="submit" id="savesettings" value="<?php esc_attr_e('Save Settings', 'directorium') ?>" class="button-secondary" />
</div>

<?php $settings->renderAll('options') ?>