<?php
\spl_autoload_register(function (string $class) : void {
	$classes = [
		'hexydec\\html\\htmldoc' => __DIR__.'/htmldoc.php',
		'hexydec\\html\\config' => __DIR__.'/config.php',
		'hexydec\\html\\tokenise' => __DIR__.'/tokenise.php',
		'hexydec\\html\\token' => __DIR__.'/tokens/interfaces/token.php',
		'hexydec\\html\\comment' => __DIR__.'/tokens/comment.php',
		'hexydec\\html\\doctype' => __DIR__.'/tokens/doctype.php',
		'hexydec\\html\\pre' => __DIR__.'/tokens/pre.php',
		'hexydec\\html\\custom' => __DIR__.'/tokens/custom.php',
		'hexydec\\html\\tag' => __DIR__.'/tokens/tag.php',
		'hexydec\\html\\text' => __DIR__.'/tokens/text.php',
		'hexydec\\html\\selector' => __DIR__.'/helpers/selector.php'
	];
	if (isset($classes[$class])) {
		require($classes[$class]);
	}
});
