<?php wp_nonce_field('directorium_custom_fields', 'directorium_fields_check') ?>
<div class="directorium">


<?php foreach ($fieldGroups as $key => $group): ?>
	<table class="horizontally-stackable">
	<?php foreach ($group as $field): ?>
		<tr>
			<?php echo $field ?>
		</tr>
	<?php endforeach ?>
	</table>
<?php endforeach ?>


</div>