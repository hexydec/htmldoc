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
		'hexydec\\html\\text' => $dir.'/tokens/text.php',
		'hexydec\\html\\cssmin' => $dir.'/cssmin.php',
		'hexydec\\css\\cssdoc' => __DIR__.'/cssdoc/cssdoc.php',
		'hexydec\\css\\document' => __DIR__.'/cssdoc/tokens/document.php',
		'hexydec\\css\\directive' => __DIR__.'/cssdoc/tokens/directive.php',
		'hexydec\\css\\rule' => __DIR__.'/cssdoc/tokens/rule.php',
		'hexydec\\css\\selector' => __DIR__.'/cssdoc/tokens/selector.php',
		'hexydec\\css\\property' => __DIR__.'/cssdoc/tokens/property.php',
		'hexydec\\css\\value' => __DIR__.'/cssdoc/tokens/value.php'
	];
	if (isset($classes[$class])) {
		return require($classes[$class]);
	}
	return false;
});
