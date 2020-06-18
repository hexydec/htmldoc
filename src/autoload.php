<?php
spl_autoload_register(function (string $class) : bool {
	$dir = __DIR__.'/htmldoc';
	$classes = [
		'hexydec\\html\\htmldoc' => $dir.'/htmldoc.php',
		'hexydec\\html\\tokenise' => $dir.'/tokenise.php',
		'hexydec\\html\\token' => $dir.'/tokens/interfaces/token.php',
		'hexydec\\html\\comment' => $dir.'/tokens/comment.php',
		'hexydec\\html\\doctype' => $dir.'/tokens/doctype.php',
		'hexydec\\html\\pre' => $dir.'/tokens/pre.php',
		'hexydec\\html\\script' => $dir.'/tokens/script.php',
		'hexydec\\html\\style' => $dir.'/tokens/style.php',
		'hexydec\\html\\tag' => $dir.'/tokens/tag.php',
		'hexydec\\html\\text' => $dir.'/tokens/text.php'
	];
	if (isset($classes[$class])) {
		return require($classes[$class]);
	}
	return false;
});
