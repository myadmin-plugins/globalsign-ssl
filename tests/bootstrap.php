<?php
require_once __DIR__.'/../vendor/autoload.php';
function myadmin_log($section, $level, $text, $line, $file) {
	//echo "{$section} {$level} {$line}@{$file}: {$text}\n";
}
