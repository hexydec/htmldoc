<?php
namespace hexydec\html;

class cdata {

	protected $content = null;

	public function parse(Array $tokens) {
		$this->content = substr($tokens[$i]['value'], 9, -3);
	}

	public function minify(Array $config) {
	}

	public function compile() {
		return $this->content === null ? '' : '<[CDATA['.$this->content.']]>';
	}
}
