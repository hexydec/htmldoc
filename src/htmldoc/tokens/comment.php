<?php
namespace hexydec\html;

class comment {

	protected $content = null;

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @param array $config An array of configuration options
	 * @return bool Whether the parser was able to capture any objects
	 */
	public function parse(array &$tokens) {
		$token = current($tokens);
		$this->content = substr($token['value'], 4, -3);
	}

	public function minify(array $minify) {
		if ($minify['comments'] && (empty($minify['comments']['ie']) || (strpos($this->content, '[if ') !== 0 && $this->content != '<![endif]'))) {
			$this->content = null;
		}
	}

	public function html() {
		return $this->content === null ? '' : '<!--'.$this->content.'-->';
	}

	public function __get($var) {
		return $this->$var;
	}
}