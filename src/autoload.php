<?php
spl_autoload_register(function ($class) {
	$dir = __DIR__.'/htmldoc';
	$classes = Array(
		'hexydec\\html\\htmldoc' => $dir.'/htmldoc.php',
		'hexydec\\html\\comment' => $dir.'/tokens/comment.php',
		'hexydec\\html\\doctype' => $dir.'/tokens/doctype.php',
		'hexydec\\html\\pre' => $dir.'/tokens/pre.php',
		'hexydec\\html\\script' => $dir.'/tokens/script.php',
		'hexydec\\html\\style' => $dir.'/tokens/style.php',
		'hexydec\\html\\tag' => $dir.'/tokens/tag.php',
		'hexydec\\html\\text' => $dir.'/tokens/text.php',
		'hexydec\\html\\cssmin' => $dir.'/cssmin.php',
		'hexydec\\html\\tokenise' => $dir.'/tokens/tokenise.php'
	);
	if (isset($classes[$class])) {
		return require($classes[$class]);
	}
	return false;
});
