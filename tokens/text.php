<?php
namespace hexydec\html;

class text {

	protected $config = Array();
	protected $content = '';

	public function __construct(Array $config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->content = html_entity_decode($token['value'], ENT_QUOTES);
	}

	public function text() {
		return $this->content;
	}

	public function minify(Array $config, object $parent = null) {

		// collapse whitespace
		if ($config['whitespace']) {
			$this->content = preg_replace('/\s++/', ' ', $this->content);

			if ($parent) {
				$children = get_class($parent) == 'hexydec\\html\\htmldoc' ? $parent->toArray() : $parent->children->toArray();
				if (($index = $this->getIndex($children)) !== false) {
					$keys = array_keys($children);
					$i = array_search($index, $keys, true);
					$inline = $this->config['elements']['inline'];
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

	public function compile(Array $config) {
		return $this->content ? htmlspecialchars($this->content) : '';
	}

	public function __get($var) {
		return $this->$var;
	}
}
