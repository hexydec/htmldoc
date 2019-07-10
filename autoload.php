<?php
spl_autoload_register(function ($class) {
	$classes = Array(
		'hexydec\\html\\htmldoc' => __DIR__.'/htmldoc.php',
		'hexydec\\html\\cdata' => __DIR__.'/tokens/cdata.php',
		'hexydec\\html\\comment' => __DIR__.'/tokens/comment.php',
		'hexydec\\html\\doctype' => __DIR__.'/tokens/doctype.php',
		'hexydec\\html\\pre' => __DIR__.'/tokens/pre.php',
		'hexydec\\html\\script' => __DIR__.'/tokens/script.php',
		'hexydec\\html\\style' => __DIR__.'/tokens/style.php',
		'hexydec\\html\\tag' => __DIR__.'/tokens/tag.php',
		'hexydec\\html\\text' => __DIR__.'/tokens/text.php',
		'hexydec\\minify\\cssmin' => __DIR__.'/cssmin.php',
		'hexydec\\minify\\tokenise' => __DIR__.'/tokenise.php'
	);
	if (isset($classes[$class])) {
		return require($classes[$class]);
	}
	return false;
});
