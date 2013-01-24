<?php
use Directorium\Listing as Listing;
use Directorium\View as View;
?>

<div class="directorium listing-editor">
	<form action="<?php esc_attr_e($action) ?>" method="post" enctype="multipart/form-data">

	<section class="title">
		<label for="listingtitle"> <?php _e('Title', 'directorium') ?> </label>
		<input type="text" name="listingtitle" id="listingtitle" value="<?php esc_attr_e($title) ?>" />
	<section>

	<section class="content">
		<label for="listingcontent"> <?php _e('Your listing content', 'directorium') ?> </label>
		<textarea name="listingcontent" id="listingcontent" cols="80" rows="10"><?php esc_html_e($content) ?></textarea>
	</section>

	<section class="geographies">
		<?php View::write('taxonomy-selector', array('terms' => $listing->annotatedTaxonomyList(Listing::TAX_GEOGRAPHY))) ?>
	</section>

	<section class="businesstypes">
		<?php View::write('taxonomy-selector', array('terms' => $listing->annotatedTaxonomyList(Listing::TAX_BUSINESS_TYPE))) ?>
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

	</form>
</div>

