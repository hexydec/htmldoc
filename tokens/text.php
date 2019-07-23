<?php
namespace hexydec\html;

class text {

	protected $root;
	protected $parent;
	protected $content = '';

	public function __construct(htmldoc $root, tag $parent = null) {
		$this->root = $root;
		$this->parent = $parent;
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @param array $config An array of configuration options
	 * @return bool Whether the parser was able to capture any objects
	 */
	public function parse(array &$tokens) {
		$token = current($tokens);
		$this->content = html_entity_decode($token['value'], ENT_QUOTES);
	}

	public function text() {
		return $this->content;
	}

	public function minify(array $minify) {

		// collapse whitespace
		if ($minify['whitespace']) {
			$parent = $this->parent;
			$this->content = preg_replace('/\s++/', ' ', $this->content);

			if ($parent) {
				$children = $parent->tagName ? $parent->toArray() : $this->root->toArray();
				if (($index = $this->getIndex($children)) !== false) {
					$keys = array_keys($children);
					$i = array_search($index, $keys, true);
					$inline = $this->root->config['elements']['inline'];
					$min = Array('hexydec\\html\\comment', 'hexydec\\html\\doctype');

					// if previous tag is a block element, ltrim the textnode
					$trim = false;
					if (!$i) {
						$trim = !in_array($parent->tagName, $inline);
					} else {
						$class = get_class($children[$i - 1]);
						if ($class == 'hexydec\\html\\tag') {
							$trim = !in_array($children[$i - 1]->tagName, $inline);
						} elseif (in_array($class, $min)) {
							$trim = true;
						}
					}
					if ($trim) {
						$this->content = ltrim($this->content);
					}

					// if next tag is a block element, rtrim the textnode
					$trim = false;
					if (!isset($keys[$i + 1])) {
						$trim = !in_array($parent->tagName, $inline);
					} else {
						$class = get_class($children[$i + 1]);
						if ($class == 'hexydec\\html\\tag') {
							$trim = !in_array($children[$i + 1]->tagName, $inline);
						} elseif (in_array($class, $min)) {
							$trim = true;
						}
					}
					if ($trim) {
						$this->content = rtrim($this->content);
					}
				}
			}
		}
	}

	protected function getIndex($children) {
		foreach ($children AS $key => $value) {
			if ($value === $this) {
				return $key;
			}
		}
		return false;
	}

	public function html() {
		return $this->content ? htmlspecialchars($this->content) : '';
	}

	public function __get($var) {
		return $this->$var;
	}
}
