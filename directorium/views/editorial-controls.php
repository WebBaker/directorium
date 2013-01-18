<?php
	use Directorium\Graphics as Graphics;
	use Directorium\Listing as Listing;

	wp_nonce_field('directorium_editorial_controls', 'directorium_editorial_check')
?>

<div class="directorium">

<p>
	<?php _e('You can enter <span class="highlight">-1</span> or <span class="highlight">No Limit</span> in relevant fields '
		.'to indicate that no limit applies. Usage levels are based on the last saved version, not live edits.',
		'directorium') ?>
</p>

<table class="listing-editorial-table">
	<tbody>
	<tr> <th> <?php _e('Assigned package', 'directorium') ?> </th> </tr>
	<tr> <td>
		<select name="editorial-package">
			<option value="0"> Unassigned </option>
		</select>
	</td> </tr>
	<tr> <th> <?php _e('Extras', 'directorium') ?> </th> </tr>
	<tr> <td>
		<input type="checkbox" name="enablelistingmap" id="enablelistingmap" class="checkbox" />
		<label for="enablelistingmap"><?php _e('Enable map', 'directorium') ?></label>
	</td> </tr>
	<tr> <td>
		<input type="checkbox" name="enablecontactform" id="enablecontactform" class="checkbox" />
		<label for="enablecontactform"><?php _e('Enable contact form', 'directorium') ?></label>
	</td> </tr>
	<tr> <th> <?php _e('Images and Videos', 'directorium') ?> </th> </tr>
	<tr> <td> <div class="wp-media-buttons">
		<?php
		Listing::alterMediaButton();
		do_action('media_buttons', 'content');
		?>
	</div> </td> </tr>
	</tbody>
</table>

<table class="listing-editorial-table">
	<thead>
		<tr>
			<th scope="col"><?php _e('Limit', 'directorium') ?></th>
			<th scope="col"><?php _e('Allowed', 'directorium') ?></th>
			<th scope="col" colspan="2"><?php _e('Usage Levels', 'directorium') ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>
				<label for="wordlimit"><?php _e('Words', 'directorium') ?></label>
				<?php
					$limit = $listing->getLimit('word');
					$count = $listing->getWordCount();
				?>
			</td>
			<td>
				<input type="text" name="wordlimit" id="wordlimit" value="<?php esc_attr_e($limit) ?>" />
			</td>
			<td class="textright">
				<pre> <?php esc_html_e($count) ?> </pre>
			</td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
		<tr>
			<td>
				<label for="charlimit"><?php _e('Characters', 'directorium') ?></label>
				<?php
					$limit = $listing->getLimit('char');
					$count = $listing->getCharacterCount();
				?>
			</td>
			<td>
				<input type="text" name="charlimit" id="charlimit" value="<?php esc_attr_e($limit) ?>" />
			</td>
			<td class="textright"> <pre> <?php esc_html_e($count) ?> </pre> </td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
		<tr>
			<td>
				<label for="imagelimit"><?php _e('Images', 'directorium') ?></label>
				<?php
					$limit = $listing->getLimit('image');
					$count = $listing->getImageCount();
				?>
			</td>
			<td>
				<input type="text" name="imagelimit" id="imagelimit" value="<?php esc_attr_e($limit) ?>" />
			</td>
			<td class="textright"> <pre> <?php esc_html_e($count) ?> </pre> </td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
		<tr>
			<td>
				<label for="videolimit"><?php _e('Videos', 'directorium') ?></label>
				<?php
				$limit = $listing->getLimit('video');
				$count = $listing->getVideoCount();
				?>
			</td>
			<td>
				<input type="text" name="videolimit" id="videolimit" value="<?php esc_attr_e($limit) ?>" />
			</td>
			<td class="textright"> <pre> <?php esc_html_e($count) ?> </pre> </td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
		<tr>
			<td>
				<label for="btypeslimit"><?php _e('Business Types', 'directorium') ?></label>
				<?php
					$limit = $listing->getLimit('btypes');
					$count = $listing->getTaxonomyCount(Listing::TAX_BUSINESS_TYPE);
				?>
			</td>
			<td>
				<input type="text" name="btypeslimit" id="btypeslimit" value="<?php echo esc_attr_e($limit) ?>" />
			</td>
			<td class="textright"> <pre> <?php esc_html_e($count) ?> </pre> </td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
		<tr>
			<td>
				<label for="btypeslimit"><?php _e('Geographies', 'directorium') ?></label>
				<?php
					$limit = $listing->getLimit('geos');
					$count = $listing->getTaxonomyCount(Listing::TAX_GEOGRAPHY);
				?>
			</td>
			<td>
				<input type="text" name="geoslimit" id="geoslimit" value="<?php echo esc_attr_e($limit) ?>" />
			</td>
			<td class="textright"> <pre> <?php esc_html_e($count) ?> </pre> </td>
			<td> <?php Graphics::meter($limit, $count) ?> </td>
		</tr>
	</tbody>
</table>

</div>