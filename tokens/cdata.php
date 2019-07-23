<?php
namespace hexydec\html;

class cdata {

	protected $content = null;

	public function parse(array &$tokens) {
		$this->content = substr($tokens[$i]['value'], 9, -3);
	}

	public function minify() {
	}

	public function html() {
		return $this->content === null ? '' : '<[CDATA['.$this->content.']]>';
	}

	public function __get($var) {
		return $this->$var;
	}
}
