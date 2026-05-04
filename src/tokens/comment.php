<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class comment implements token {

	/**
	 * @var string The text content of this object
	 */
	protected ?string $content = null;

	/**
	 * Constructs the comment object
	 *
	 * @param htmldoc $root The parent htmldoc object
	 */
	public function __construct(htmldoc $root) {

	}

	/**
	 * Parses the next HTML component from a tokenise object
	 *
	 * @param tokenise $tokens A tokenise object
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {
		if (($token = $tokens->current()) !== null) {
			$this->content = \mb_substr($token['value'], 4, -3);
		}
	}

	/**
	 * Minifies the internal representation of the comment
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {
		if (!empty($minify['comments']['remove']) && $this->content) {
			if (empty($minify['comments']['ie']) || (\mb_strpos($this->content, '[if ') !== 0 && $this->content !== '<![endif]')) {
				$this->content = null;
			}
		}
	}

	/**
	 * Compile the comment as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		return $this->content === null ? '' : '<!--'.$this->content.'-->';
	}
}
