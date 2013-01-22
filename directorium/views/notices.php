<?php namespace Directorium; ?>

<?php if (!isset($notices) or !is_array($notices)) return ?>

<div id="notices" class="notices">
	<?php foreach ($notices as $type => $notice): ?>
		<div class="notice<?php if (is_string($type)) echo ' '.esc_attr($type) ?>">
				<?php foreach ($notice as $individualNotice) echo '<p>'.esc_html($individualNotice).'</p>' ?>
		</div>
	<?php endforeach ?>
</div>