<?php
namespace hexydec\html;

class pre {

	protected $content = null;

	public function parse(Array &$tokens) {
		$value = '';
		while (($token = next($tokens)) !== false && $token['type'] != 'tagclose') {
			$value .= $token['value'];
		}
		if ($value) {
			$this->content = html_entity_decode($value, ENT_QUOTES);
		}
	}

	public function text() {
		return $this->content;
	}

	public function minify(Array $config) {
	}

	public function compile(Array $config) {
		return htmlspecialchars($this->content);
	}

	public function __get($var) {
		return $this->$var;
	}
}
