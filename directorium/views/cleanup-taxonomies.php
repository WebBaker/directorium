<?php
$cleanupLink = '';

/**
 * We're using a foreach to extract the taxonomy name. We only expect the foreach
 * loop to run once.
 */
if (count($taxonomyCleanup) > 0) {
	$query = array(
		'post_type' => Directorium\Listing::POST_TYPE,
		'page' => 'import',
		'check' => wp_create_nonce('doTaxonomyCleanup') );

	$cleanupLink = get_admin_url(null, 'edit.php').'?'.http_build_query($query, '', '&');
}
?>

<div id="cleanup-action">
	<h4><?php _e('Cleanup tasks') ?></h4>

	<p><?php _e('If this message does not change please click on the following link to force the cleanup process to complete.', 'directorium') ?>
		<a href="<?php echo esc_attr($cleanupLink) ?>"><?php _e('Force Cleanup!', 'directorium') ?></a></p>
</div>

<script type="text/javascript">
	var directoriumText = {
		success: "<?php _e('Cleanup completed!', 'directorium') ?>"
	}
</script>