<?php
/**
 * Builds each row of the taxonomy selector table. Works recursively to render children.
 */
$iterator = function($term, $iterator, $level = 0) {
	static $stripe = true;
	$stripe = $stripe ? false: true;

	$class = $stripe ? ' class="stripe"' : '';
	echo '<tr'.$class.'> <td>';

	// Spacer markings to indicate hierarchy
	if ($level > 0) echo '<span class="level-spacers">'.str_repeat('&ndash;', $level).'</span>';

	// Selection checkbox
	echo '<input type="checkbox" name="term-selection['.esc_attr($term->taxonomy).']['.esc_attr($term->term_id).']" value="1" ';
	if ($term->in_use) echo 'checked="checked" ';
	echo '/>';

	// Term name
	echo '</td> <td>';
	echo esc_html($term->name);
	echo '</td> </tr>';

	// Render any child terms
	if (!empty($term->children)) foreach ($term->children as $child)
		$iterator($child, $iterator, $level + 1);
}
?>

<div class="option-box">
	<label> <?php esc_html_e($label) ?> </label>

	<div class="tax-selector-box<?php esc_attr_e(' '.$limitTerm) ?>">
		<table> <?php foreach ($terms as $term) $iterator($term, $iterator); ?> </table>
	</div>

	<dl class="editorialcontrol">
		<?php if ($listing->getLimit($limitTerm) > 0): ?>
			<dt class="<?php esc_attr_e($limitTerm) ?>count"><?php _e('Term count', 'directorium') ?></dt>
			<dd class="<?php esc_attr_e($limitTerm) ?>count"><?php printf(__('%d of %d', 'directorium'), $listing->getTaxonomyCount($taxonomy), $listing->getLimit($limitTerm)) ?></dd>
		<?php endif ?>
	</dl>
</div>

