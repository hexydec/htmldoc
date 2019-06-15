<?php
namespace hexydec\html;

class comment {

	protected $comment = null;

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->comment = substr($token['value'], 4, -3);
	}

	public function minify(Array $config) {
		if ($config['comments'] && (empty($config['comments']['ie']) || (strpos($this->comment, '[if ') !== 0 && $this->comment != '<![endif]'))) {
			$this->comment = null;
		}
	}

	public function compile() {
		return $this->comment === null ? '' : '<!--'.$this->comment.'-->';
	}
}
