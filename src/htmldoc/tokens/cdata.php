<?php
namespace hexydec\html;

class cdata {

	protected $content = null;

	public function parse(array &$tokens) {
		$this->content = substr($tokens[$i]['value'], 9, -3);
	}

	/**
	 * Minifies the internal representation of the CDATA object, currently does nothing
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify() : void {
	}

	public function html() : string {
		return $this->content === null ? '' : '<[CDATA['.$this->content.']]>';
	}

	public function __get($var) {
		return $this->$var;
	}
}
