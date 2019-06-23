<?php
namespace hexydec\html;

class style {

	protected $config = Array();
	protected $value = null;

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
		if ($value) {
			$this->value = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['css'] && $this->value) {
			$this->value = call_user_func($config['css'], $this->value);
		} else {
			$this->value = trim($this->value);
		}
	}

	public function compile(Array $config) {
		return $this->value;
	}
}
