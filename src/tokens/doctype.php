<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class doctype implements token {

	/**
	 * @var array The text content of this object
	 */
	protected array $content = [];

	/**
	 * Constructs the script object
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
		$types = ['attribute', 'quotes'];
		$content = [];
		while (($token = $tokens->next()) !== null && $token['type'] !== 'tagopenend') {
			if (in_array($token['type'], $types, true)) {
				$content[] = \html_entity_decode(\ltrim($token['value']));
			}
		}
		$this->content = $content;
	}

	/**
	 * Minifies the internal representation of the doctype
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {
		foreach ($this->content AS &$item) {
			if ($minify['lowercase'] && strcspn($item, '"\'', 0, 1) === 1) {
				$item = \mb_strtolower($item);
			}
		}
	}

	/**
	 * Compile the tag as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		$html = '<!DOCTYPE';
		foreach ($this->content AS $item) {

			// unquoted
			if (strcspn($item, '"\'', 0, 1) === 1) {
				$html .= ' '.$item;

			} else {
				$item = trim($item, '"\'');

				// single or minimal
				if ($options['quotestyle'] === 'single' || ($options['quotestyle'] === 'minimal' && strpos($item, '"') !== false)) {
					$html .= " '".\str_replace(['&', "'", '<'], ['&amp;', '&#39;', '&lt;'], $item)."'";

				// double quotes
				} else {
					$html .= ' "'.\str_replace(['&', '"', '<'], ['&amp;', '&quot;', '&lt;'], $item).'"';
				}
			}
		}
		return $html.'>';
	}
}
