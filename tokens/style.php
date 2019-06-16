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
		while (($token = next($tokens)) !== false && ($token['type'] != 'tagclose' || $token['value'] != '</style>')) {
			$value .= $token['value'];
		}
		if ($value) {
			$this->value = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['cssmin'] && $this->value) {
			$this->value = call_user_func($config['cssmin'], $this->value);
		} else {
			$this->value = trim($this->value);
		}
	}

	public function compile() {
		return $this->value;
	}
}
