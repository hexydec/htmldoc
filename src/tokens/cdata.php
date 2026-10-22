<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class cdata implements token {

	/**
	 * @var string The content of the CDATA section
	 */
	protected string $content = '';

	/**
	 * @var tag|null The parent tag object
	 */
	public ?tag $parent = null;

	/**
	 * Constructs the CDATA object
	 *
	 * @param htmldoc $root The parent htmldoc object
	 * @param tag $parent The parent tag object
	 */
	public function __construct(htmldoc $root, ?tag $parent = null) {
		$this->parent = $parent;
	}

	/**
	 * Parses the next HTML component from a tokenise object
	 *
	 * @param tokenise $tokens A tokenise object
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {
		if (($token = $tokens->current()) !== null) {
			$this->content = \mb_substr($token['value'], 9, -3);
		}
	}

	/**
	 * Retrieves the content of the CDATA section
	 *
	 * @return string The content of the CDATA section
	 */
	public function text() : string {
		return $this->content;
	}

	/**
	 * CDATA sections are output as they are, so there is nothing to minify
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {

	}

	/**
	 * Compile the CDATA section as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		return '<![CDATA['.$this->content.']]>';
	}
}
