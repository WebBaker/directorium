<?php
namespace Directorium;
use Directorium\Helpers\View as View;
?>

<div class="wrap directorium">

	<div class="icon32"></div>
	<h2><?php echo isset($title) ? $title : _e('Directory Admin Page', 'directorium') ?> </h2>

	<br />

	<?php
		if (isset($notices) and is_array($notices) and count($notices) > 0)
			View::write('notices', array('notices' => $notices))
	?>

	<?php
		if (isset($action)) echo '<form action="'.$action.'" method="post" enctype="multipart/form-data">';
			if (isset($content)) echo $content;
		if (isset($action)) echo '</form>';
	?>

</div>

<script type="text/javascript" src="<?php echo Core()->url.'/assets/common-admin.js' ?>"></script>