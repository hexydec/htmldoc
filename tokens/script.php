<?php
namespace hexydec\html;

class script {

	protected $config = Array();
	protected $value = null;

	public function __construct($config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$value = '';
		while (($token = next($tokens)) !== false && ($token['type'] != 'tagclose' || $token['value'] != '</script>')) {
			$value .= $token['value'];
		}
		if ($value) {
			$this->value = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['jsmin'] && $this->value) {
			$this->value = call_user_func($config['jsmin'], $this->value);
		} else {
			$this->value = trim($this->value);
		}
	}

	public function compile() {
		return $this->value;
	}
}
