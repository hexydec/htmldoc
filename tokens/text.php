<?php
namespace hexydec\html;

class text {

	protected $config = Array();
	public $value = '';

	public function __construct(Array $config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->value = html_entity_decode($token['value'], ENT_QUOTES);
	}

	public function minify(Array $config, object $parent = null) {

		// collapse whitespace
		if ($config['whitespace']) {
			$this->value = preg_replace('/\s++/', ' ', $this->value);

			if ($parent) {
				$children = $parent->children()->toArray();
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
						$this->value = ltrim($this->value);
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
						$this->value = rtrim($this->value);
					}
				}
			}

			// if the textnode is empty, remove it
			if ($this->value === '') {
				$this->value = null;
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
		return $this->value ? htmlspecialchars($this->value) : '';
	}
}
