<?php
namespace hexydec\html;

class doctype {

	protected $type = Array();

	public function parse(Array &$tokens) {
		$this->type = Array();
		while (($token = next($tokens)) !== false && $token['type'] != 'tagopenend') {
			if ($token['type'] == 'attribute') {
				$this->type[]  = html_entity_decode($token['value']);
			}
		}
	}

	public function minify() {

	}

	public function compile(Array $config) {
		return '<!DOCTYPE '.implode(' ', $this->type).'>';
	}

	public function __get($var) {
		return $this->$var;
	}
}
