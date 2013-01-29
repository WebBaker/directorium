<?php
use Directorium\Listing as Listing;
use Directorium\View as View;
?>

<div class="directorium listing-editor">
	<form action="<?php esc_attr_e($action) ?>" method="post" enctype="multipart/form-data">

	<section class="title">
		<label for="listingtitle"> <?php _e('Title', 'directorium') ?> </label>
		<input type="text" name="listingtitle" id="listingtitle" value="<?php esc_attr_e($title) ?>" class="full-width" />
	</section>

	<section class="content">
		<label for="listingcontent"> <?php _e('Your listing content', 'directorium') ?> </label>
		<textarea name="listingcontent" id="listingcontent" cols="80" rows="10" class="full-width"><?php esc_html_e($content) ?></textarea>
	</section>

	<section class="taxonomies">
		<?php View::write('taxonomy-selector', array('terms' => $listing->annotatedTaxonomyList(Listing::TAX_GEOGRAPHY), 'label' => __('Geography', 'directorium')) ) ?>
		<?php View::write('taxonomy-selector', array('terms' => $listing->annotatedTaxonomyList(Listing::TAX_BUSINESS_TYPE), 'label' => __('Business Type', 'directorium')) ) ?>
	</section>

	<?php foreach ($listing->getCustomFields() as $group): ?>
		<section class="fields">
			<?php foreach ($group as $field): ?>
				<?php echo $field ?>
			<?php endforeach ?>
		</section>
	<?php endforeach ?>

	<section class="media images">
	</section>

	<section class="media videos">
	</section>

	<section class="controls">
		<input type="submit" name="submit" value="<?php esc_attr_e('Submit', 'directorium') ?>" class="positive-action" />
		<input type="submit" name="take-offline" value="<?php esc_attr_e('Take offline', 'directorium') ?>" class="dangerous-action" />
		<input type="submit" name="kill-amendment" value="<?php esc_attr_e('Cancel amendment', 'directorium') ?>" class="requires-caution" />
	</section>

	</form>
</div>

