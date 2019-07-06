<?php
namespace hexydec\html;

class comment {

	protected $content = null;

	public function parse(Array &$tokens) {
		$token = current($tokens);
		$this->content = substr($token['value'], 4, -3);
	}

	public function minify(Array $config) {
		if ($config['comments'] && (empty($config['comments']['ie']) || (strpos($this->content, '[if ') !== 0 && $this->content != '<![endif]'))) {
			$this->content = null;
		}
	}

	public function compile(Array $config) {
		return $this->content === null ? '' : '<!--'.$this->content.'-->';
	}

	public function __get($var) {
		return $this->$var;
	}
}
