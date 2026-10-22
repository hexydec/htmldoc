<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class text implements token {

	/**
	 * @var htmldoc The parent htmldoc object
	 */
	protected htmldoc $root;

	/**
	 * @var tag|null The parent tag object
	 */
	public ?tag $parent = null;

	/**
	 * @var string The text content of this object
	 */
	protected string $content = '';

	/**
	 * Constructs the token
	 *
	 * @param htmldoc $root The parent HTMLdoc object
	 * @param tag $parent The parent tag object
	 */
	public function __construct(htmldoc $root, ?tag $parent = null) {
		$this->root = $root;
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
			$this->content = \html_entity_decode($token['value'], ENT_QUOTES | ENT_HTML5);
		}
	}

	public function text() : string {
		return $this->content;
	}

	/**
	 * Minifies the internal representation of the text object
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {

		// collapse whitespace
		if ($minify['whitespace']) {
			$parent = $this->parent;
			$this->content = \preg_replace('/[\\n\\t ]++/u', ' ', \str_replace("\r", '', $this->content));

			if ($parent) {
				$children = $parent->toArray();
				if (($index = $this->getIndex($children)) !== false) {
					$keys = \array_keys($children);
					$i = \array_search($index, $keys, true);
					$root = $this->root;
					$parentInline = $parent->tagNameLower !== null && $root->lookup('inline', $parent->tagNameLower) !== null;

					// if previous tag is a block element, or a comment or doctype, ltrim the textnode
					$trim = false;
					if (!$i) {
						$trim = !$parentInline;
					} else {
						$item = $children[$i - 1];
						if ($item instanceof tag) {
							$trim = $root->lookup('inline', $item->tagNameLower) === null && $root->lookup('inlineblock', $item->tagNameLower) === null;
						} elseif ($item instanceof comment || $item instanceof doctype) {
							$trim = true;
						}
					}
					if ($trim) {
						$this->content = \ltrim($this->content, " \t\r\n");
					}

					// if next tag is a block element, or a comment or doctype, rtrim the textnode
					$trim = false;
					if (!isset($keys[$i + 1])) {
						$trim = !$parentInline;
					} else {
						$item = $children[$i + 1];
						if ($item instanceof tag) {
							$trim = $root->lookup('inline', $item->tagNameLower) === null && $root->lookup('inlineblock', $item->tagNameLower) === null;
						} elseif ($item instanceof comment || $item instanceof doctype) {
							$trim = true;
						}
					}
					if ($trim) {
						$this->content = \rtrim($this->content, " \t\r\n");
					}
				}
			}
		}
	}

	protected function getIndex(array $children) : int|false {
		foreach ($children AS $key => $value) {
			if ($value === $this) {
				return $key;
			}
		}
		return false;
	}

	/**
	 * Compile the text as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		return $this->content !== '' ? \htmlspecialchars($this->content, ENT_NOQUOTES | ENT_HTML5) : '';
	}
}
