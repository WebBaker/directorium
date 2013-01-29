<?php
use Directorium\Listing as Listing;
?>

<?php if ($isLoggedIn): ?>

	<?php if (count($listings) > 0): ?>

		<p> <?php printf(_n('You currently have %d listing.', 'You currently have %d listings.', count($listings), 'directorium'), count($listings)) ?> </p>

		<ul> <?php foreach ($listings as $listingID): ?>

			<?php
				$listing = Listing::getPost($listingID);
				$editorLink = $public->editorLink($listingID);
			?>

			<li>
				<a href="<?php esc_attr_e($editorLink) ?>" title="<?php _e('Edit this listing', 'directorium') ?>">
					<?php esc_html_e($listing->post->post_title) ?>
				</a>
			</li>

		<?php endforeach ?> </ul>

	<?php else: ?>

		<p> <?php _e('You do not currently have any listings. Please contact the site administrator or purchase a listing.', 'directorium') ?> </p>

	<?php endif ?>

<?php else: ?>

	<p> <?php _e('To view any directory listings that you own you must first log in.', 'directorium') ?> </p>

<?php endif ?>