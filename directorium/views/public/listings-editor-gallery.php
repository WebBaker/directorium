<?php namespace Directorium; ?>

<label> <?php _e('Attached images', 'directorium') ?> </label>

<div class="attachedimages">
<?php
$images = $listing->getAttachedImages();
foreach ($images as $image): ?>
	<div class="attachment">
		<?php echo wp_get_attachment_image($image->ID, ListingAdmin::IMG_PUBLIC_PREVIEW_SIZE, false, array('class' => 'attachment-preview')) ?>
		<img src="<?php esc_attr_e(Core()->url) ?>/assets/crystal-remove.png" title="<?php esc_attr_e('Remove attachment', 'directorium') ?>" alt="Remove icon" class="removeattachedimageicon" />
		<input type="checkbox" name="listingremoveimage[]" value="<?php esc_attr_e($image->ID) ?>" />
		<label><?php _e('Remove this image', 'directorium') ?></label>
	</div>
<?php endforeach ?>
</div>

<div class="imageuploadinputs">
	<div class="inputfields">
		<div class="uploadinput">
			<input type="file" name="newlistingimage-1" size="60" />
			<img src="<?php esc_attr_e(Core()->url) ?>/assets/crystal-remove.png" title="<?php esc_attr_e('Remove', 'directorium') ?>" alt="Remove icon" class="removeimageinput" />
		</div>
	</div>
	<div class="actions">
		<span class="button" id="addimageuploadinput">
			<img src="<?php esc_attr_e(Core()->url) ?>/assets/crystal-add.png" title="<?php esc_attr_e('Add another image') ?>" alt="Add icon" />
			<?php _e('Add another image', 'directorium') ?>
		</span>
	</div>
</div>