<section>
	<h4> <?php esc_html_e($label) ?> </h4>

	<ul class="settingslist">
		<?php foreach ($keys as $key): ?>
		<li>
			<?php $settings->get($key, true); ?>
		</li>
		<?php endforeach ?>
	</ul>
</section>