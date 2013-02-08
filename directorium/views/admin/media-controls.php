<?php
use Directorium\Listing as Listing;
use Directorium\ListingAdmin as ListingAdmin;

wp_nonce_field('directorium_media_controls', 'directorium_media_check');

$assets = Directorium\Core::$plugin->url.'/assets';
$imageCount = 0;
$videoCount = 0;
?>

<ul class="medialist">
<?php foreach ($listing->getAttachedImages() as $image): ?>
	<?php $imageCount++ ?>
	<li>
		<div class="mediapreview">
			<?php echo wp_get_attachment_image($image->ID, ListingAdmin::IMG_PREVIEW_SIZE, false, array(
				'title' => esc_attr($image->post_title)
			)); ?>
		</div>
		<div class="mediasettings">
			<?php $meta = wp_get_attachment_metadata($image->ID) ?>
			<table>
				<tr>
					<th><?php _e('Name', 'directorium') ?></th>
					<td><input type="text" readonly="readonly" value="<?php esc_attr_e($image->post_title) ?>" /></td>
					<th><?php _e('Action', 'directorium') ?></th>
					<td class="actions">
						<select class="remove-media-options" name="attachment[<?php esc_attr_e($image->ID) ?>][action]">
							<option value="do-nothing" selected="selected"> &mdash; </option>
							<option value="detach"><?php _e('Detach from listing', 'directorium') ?></option>
							<option value="delete"><?php _e('Delete permanently', 'directorium') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="col"><?php _e('Caption', 'directorium') ?></th>
					<td colspan="3"><input type="text" class="longfield" name="attachment[<?php esc_attr_e($image->ID) ?>][caption]" value="<?php esc_attr_e($meta['image_meta']['caption']) ?>" /></td>
				</tr>
				<tr>
					<th scope="col"><?php _e('Title', 'directorium') ?></th>
					<td colspan="3"><input type="text" class="longfield" name="attachment[<?php esc_attr_e($image->ID) ?>][title]" value="<?php esc_attr_e($meta['image_meta']['title']) ?>" /></td>
				</tr>
				<tr>

				</tr>
			</table>
		</div>
	</li>
<?php endforeach ?>
<?php foreach ($listing->getAttachedVideos() as $video): ?>
	<?php $videoCount++ ?>
	<li>
		<div class="mediapreview">
			<?php // Load video preview ?>
		</div>
		<div class="mediasettings">
			<?php $meta = wp_get_attachment_metadata($video->ID) ?>
			<table>
				<tr>
					<th><?php _e('Name', 'directorium') ?></th>
					<td><input type="text" readonly="readonly" value="<?php esc_attr_e($video->post_title) ?>" /></td>
					<th><?php _e('Action', 'directorium') ?></th>
					<td class="actions">
						<select class="remove-media-options">
							<option value="do-nothing" selected="selected"> &mdash; </option>
							<option value="detach-<?php esc_attr_e($video->ID) ?>"><?php _e('Detach from listing', 'directorium') ?></option>
							<option value="delete-<?php esc_attr_e($video->ID) ?>"><?php _e('Delete permanently', 'directorium') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="col"><?php _e('Caption', 'directorium') ?></th>
					<td colspan="3"><input type="text" class="longfield" name="attachment[caption][]" value="<?php esc_attr_e($meta['image_meta']['caption']) ?>" /></td>
				</tr>
				<tr>
					<th scope="col"><?php _e('Title', 'directorium') ?></th>
					<td colspan="3"><input type="text" class="longfield" name="attachment[caption][]" value="<?php esc_attr_e($meta['image_meta']['title']) ?>" /></td>
				</tr>
				<tr>

				</tr>
			</table>
		</div>
	</li>
<?php endforeach ?>
<?php
	if ($imageCount === 0 or $videoCount ===0) echo '<li>';

	if ($imageCount === 0 and $videoCount > 0)
		_e('No images have been attached to this listing.', 'directorium');
	elseif ($imageCount > 0 and $videoCount === 0)
		_e('No videos have been attached to this listing.', 'directorium');
	elseif ($imageCount > 0 and $videoCount === 0)
		_e('No videos have been attached to this listing.', 'directorium');
	else
		_e('No images or videos have been attached to this listing.', 'directorium');

?> <?php

	if ($imageCount === 0 or $videoCount ===0) {
		_e("You can add new media using WordPress's media tools. ", 'directorium');
		media_buttons('content');
		echo '</li>';
	}
?>
</ul>