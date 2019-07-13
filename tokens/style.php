<?php
namespace hexydec\html;

class style {

	protected $config = Array();
	protected $content = null;

	public function __construct(array $config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$value = '';
		$token = current($tokens);
		while ($token !== false && ($token['type'] != 'tagclose' || $token['value'] != '</style>')) {
			$value .= $token['value'];
			$token = next($tokens);
		}
		prev($tokens);
		if ($value) {
			$this->content = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['css'] && $this->content) {
			$this->content = call_user_func($config['css'], $this->content);
		} else {
			$this->content = trim($this->content);
		}
	}

	public function compile(Array $config) {
		return $this->content;
	}

	public function __get($var) {
		return $this->$var;
	}
}
