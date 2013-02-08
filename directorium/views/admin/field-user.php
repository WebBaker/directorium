<td>
	<?php if (empty($label) === false): ?>
		<label for="<?php echo esc_attr($name) ?>">
			<?php echo esc_html($label) ?>
		</label>
	<?php endif ?>
</td>

<td>
	<input type="text"
		   name="<?php echo esc_attr($name) ?>"
		   id="<?php echo esc_attr($id) ?>"
		   value="<?php echo esc_attr($default) ?>" />
</td>