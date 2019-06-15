<?php
namespace hexydec\html;

class text {

	public $value = '';

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->value = html_entity_decode($token['value'], ENT_QUOTES);
	}

	public function minify(Array $config, String &$parentTag = null) {

		// collapse whitespace
		if ($config['whitespace']) {
			$this->value = preg_replace('/\s++/', ' ', $this->value);

			// if last tag is a block element, ltrim the textnode
			// if (!in_array($lasttag ? $lasttag : $parentTag, $this->config['elements']['inline'])) {
			// 	$this->text = ltrim($this->text);
			// }
			//
			// // if next tag is a block element, rtrim the textnode
			// $tag = isset($ast[$i + 1]['tag']) ? $ast[$i + 1]['tag'] : $parentTag; // if last element use parent
			// if (!in_array($tag, $this->config['elements']['inline'])) {
			// 	$this->text = rtrim($this->text);
			// }

			// if the textnode is empty, remove it
			if ($this->value === '') {
				$this->value = null;
			}
		}
	}

	public function compile() {
		return htmlspecialchars($this->value);
	}
}
