<?php
namespace hexydec\html;

class pre {

	protected $value = null;

	public function parse(Array $tokens) : Array {
		$value = '';
		while (($token = next($tokens)) !== false && $token['type'] != 'tagclose') {
			$value .= $token['value'];
		}
		if ($value) {
			$this->value = html_entity_decode($value, ENT_QUOTES);
		}
	}

	public function minify(Array $config) {
	}

	public function compile(Array $config) {
		return htmlspecialchars($this->value);
	}
}
