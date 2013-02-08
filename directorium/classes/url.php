<?php
namespace Directorium;


class URL {
	public static function generate($path, array $query = null) {
		if ($query !== null) $query = http_build_query($query);
		else $query = '';

		// Pretty permalinks or ugly? If a question mark exists in $path we can assume ugly
		if (strpos($path, '?') !== false) {
			$url = get_home_url().$path.$query;
		}
		else {
			$url = trailingslashit(get_site_url()).$path;
			$url = trailingslashit($url).'?'.$query;
		}
		return $url;
	}
}