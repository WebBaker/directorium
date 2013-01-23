<?php
wp_nonce_field('directorium_owner_controls', 'directorium_owner_check');
$assets = Directorium\Core::$plugin->url.'/assets';
$zebra = 'initially unstriped';
?>

<div class="ownerslist">
	<p> <?php _e('<strong>Current owners</strong> are listed below, you can remove them, email them or edit their profiles.', 'directorium') ?> </p>
	<?php if (count($owners) === 0): ?>
		<p class="notice"> <?php _e('Currently no users have ownership of this listing.', 'directorium') ?> </p>
	<?php else: ?>
		<div class="scrollablelist"> <table class="userlist">
		<?php foreach ($owners as $owner): ?>
			<?php $zebra = strlen($zebra) > 0 ? '' : ' class="stripe"' ?>
			<tr<?php echo $zebra ?>>
				<?php $user = get_user_by('id', $owner) ?>
				<td class="textright"> #<?php esc_html_e($user->ID) ?> </td>
				<td> <strong> <?php esc_html_e($user->user_nicename) ?> </strong> </td>
				<td class="actions">
					<a href="<?php esc_attr_e('mailto:'.$user->user_email) ?>">
						<img src="<?php esc_attr_e($assets.'/crystal-mail.png') ?>" alt="Remove owner icon" title="<?php esc_attr_e('Send email to owner', 'directorium') ?>" />
					</a>
				</td>
				<td class="actions">
					<?php $link = get_admin_url(null, 'user-edit.php?user_id='.$user->ID) ?>
					<a href="<?php esc_attr_e($link) ?>">
						<img src="<?php esc_attr_e($assets.'/crystal-edit.png') ?>" alt="Remove owner icon" title="<?php esc_attr_e('Edit owner profile', 'directorium') ?>" />
					</a>
				</td>
				<td class="actions danger">
					<button name="removeowner" value="<?php esc_attr_e($user->ID) ?>">
						<img src="<?php esc_attr_e($assets.'/crystal-stop-cancel.png') ?>" alt="Remove owner icon" title="<?php esc_attr_e('Remove owner', 'directorium') ?>" />
					</button>
				</td>
			</tr>
		<?php endforeach ?>
		</table> </div>
	<?php endif ?>
</div>
<div class="owneradd">
	<p> <?php _e('<strong>To add owners</strong> enter the ID (or comma separated IDs) of any users you wish to assign ownership to.', 'directorium') ?> </p>
	<p> <input type="text" name="addownerids" value="" /> </p>
</div>
