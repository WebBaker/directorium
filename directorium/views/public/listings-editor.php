<?php
namespace Directorium;
use Directorium\Helpers\View as View;
?>

<div class="directorium listing-editor">

	<?php if (isset($errors) and count($errors) >= 1): ?>
		<ul class="warnings">
		<?php foreach ($errors as $error): ?>
			<li><?php esc_html_e($error) ?></li>
		<?php endforeach ?>
		</ul>
	<?php endif ?>

	<form action="<?php esc_attr_e($action) ?>" method="post" enctype="multipart/form-data">

	<?php wp_nonce_field('listingsubmission', 'validatelistingupdate') ?>
	<input type="hidden" name="listingid" value="<?php esc_attr_e(stripslashes($listing->id)) ?>" />

	<section class="title">
		<label for="listingtitle"> <?php _e('Title', 'directorium') ?> </label>
		<input type="text" name="listingtitle" id="listingtitle" value="<?php esc_attr_e(stripslashes($title)) ?>" class="full-width" />
	</section>

	<section class="content">
		<label for="listingcontent"> <?php _e('Your listing content', 'directorium') ?> </label>
		<textarea name="listingcontent" id="listingcontent" cols="80" rows="10" class="full-width"><?php esc_html_e(stripslashes($content)) ?></textarea>
		<dl class="editorialcontrol">
			<?php if ($listing->getLimit('word') > 0): ?>
				<dt class="wordcount"><?php _e('Word count', 'directorium') ?></dt>
				<dd class="wordcount"><?php printf(__('%d of %d', 'directorium'), $listing->getWordCount(), $listing->getLimit('word')) ?></dd>
			<?php endif ?>
			<?php if ($listing->getLimit('char') > 0): ?>
				<dt class="charcount"><?php _e('Character count', 'directorium') ?></dt>
				<dd class="charcount"><?php printf(__('%d of %d', 'directorium'), $listing->getCharacterCount(), $listing->getLimit('char')) ?></dd>
			<?php endif ?>
		</dl>
	</section>

	<section class="taxonomies">
		<?php View::write('taxonomy-selector', array(
			'terms' => $listing->annotatedTaxonomyList(Listing::TAX_GEOGRAPHY),
			'label' => __('Geography', 'directorium'),
			'listing' => $listing,
			'limitTerm' => 'geos',
			'taxonomy' => Listing::TAX_GEOGRAPHY) )
		?>
		<?php View::write('taxonomy-selector', array(
			'terms' => $listing->annotatedTaxonomyList(Listing::TAX_BUSINESS_TYPE),
			'label' => __('Business Type', 'directorium'),
			'listing' => $listing,
			'limitTerm' => 'btypes',
			'taxonomy' => Listing::TAX_BUSINESS_TYPE) )
		?>
	</section>

	<?php foreach ($listing->getCustomFields() as $group): ?>
		<section class="fields">
			<?php foreach ($group as $field): ?>
				<?php echo $field ?>
			<?php endforeach ?>
		</section>
	<?php endforeach ?>

	<section class="media images">
		<?php View::write('listings-editor-gallery', array('listing' => $listing)) ?>
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

