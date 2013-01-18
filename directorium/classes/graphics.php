<?php
namespace Directorium;


class Graphics {
	public static function meter($limit, $usage, $width = 100, $flag = true) {
		if ($limit === -1) {
			$limit = 100;
			$usage = 1;
		}
		elseif ($limit === 0) {
			$limit = 1;
			$usage = 2;
		}

		$value = (int) $usage / $limit;
		if ($value > 1) { $value = 1; $class = 'over'; }
		else $class = 'within';

		$width = $value * $width;

		echo '<div class="usage-meter"> <div style="width:'.$width.'px" class="'.$class.'"></div> </div>';

		if ($class === 'over' and $flag) {
			$src = Core::$plugin->url.'/assets/crystal-warning.png';
			$alt = __('Warning flag', 'directorium');
			$title = __('Permitted limits exceeded', 'directorium');

			echo '<img src="'.$src.'" alt="'.$alt.'" title="'.$title.'" class="meter-flag" />';
		}
	}
}