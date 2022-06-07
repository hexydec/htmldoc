<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

/**
 *
 */
interface token {

	/**
	 * Constructs the token
	 *
	 * @param htmldoc $root The parent HTMLdoc object
	 */
	public function __construct(htmldoc $root);

	/**
	 * Parses the next HTML component from a tokenise object
	 *
	 * @param tokenise $tokens A tokenise object
	 * @return void
	 */
	public function parse(tokenise $tokens) : void;

	/**
	 * Minifies the internal representation of the object
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void;

	/**
	 * Compile the tag as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string;
}
