<div class="directorium listing-editor" xmlns="http://www.w3.org/1999/html">

	<section class="title">
		<label for="listingtitle"> <?php _e('Title', 'directorium') ?> </label>
		<input type="text" name="listingtitle" id="listingtitle" value="<?php esc_attr_e($listing->post->post_title) ?>" />
	<section>

	<section class="content">
		<label for="listingtext"> <?php _e('Your listing content', 'directorium') ?> </label>
		<textarea name="listingtext" id="listingtext" cols="80" rows="10"><?php esc_html_e($listing->post->post_content) ?></textarea>
	</section>

	<section class="geographies">
		<!-- List of geographies here -->
	</section>

	<section class="businesstypes">
		<!-- List of business types here -->
	</section>

	<section class="media">
		<section class="images">
		</section>

		<section class="videos">
		</section>
	</section>

	<section class="controls">
		<input type="submit" name="submit" value="<?php esc_attr_e('Submit', 'directorium') ?>" />
		<input type="submit" name="take-offline" value="<?php esc_attr_e('Take offline', 'directorium') ?>" />
	</section>
</div>

