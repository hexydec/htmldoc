<?php
namespace hexydec\html;

class cdata {

	protected $cdata = null;

	public function parse(Array $tokens, int $count, int &$i) : Array {
		$this->cdata = substr($tokens[$i]['value'], 9, -3);
	}

	public function minify(Array $config) {
	}

	public function compile() {
		return $this->cdata === null ? '' : '<[CDATA['.$this->cdata.']]>';
	}
}
