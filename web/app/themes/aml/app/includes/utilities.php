<?php
namespace Custom\Utilities;

/*
*
* turns the likes of 'pg_susi_grantholder_2000' into PG Susi Grantholder 2000'
*/

function make_key_pretty ($key) {
	$key = str_replace('_', ' ', $key);
	$key = ucwords($key);
	return $key;
}