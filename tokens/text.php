<?php
namespace hexydec\html;

class text {

	protected $config = Array();
	protected $textContent = '';

	public function __construct(Array $config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->textContent = html_entity_decode($token['value'], ENT_QUOTES);
	}

	public function text() {
		return $this->textContent;
	}

	public function minify(Array $config, object $parent = null) {

		// collapse whitespace
		if ($config['whitespace']) {
			$this->textContent = preg_replace('/\s++/', ' ', $this->textContent);

			if ($parent) {
				$children = $parent->children();
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
						$this->textContent = ltrim($this->textContent);
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
						$this->textContent = rtrim($this->textContent);
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
		return $this->textContent ? htmlspecialchars($this->textContent) : '';
	}
}
