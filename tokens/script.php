<?php
namespace hexydec\html;

class script {

	protected $config = Array();
	protected $content = null;

	public function __construct($config) {
		$this->config = $config;
	}

	public function parse(Array &$tokens) {
		$value = '';
		$token = current($tokens);
		while ($token !== false && ($token['type'] != 'tagclose' || $token['value'] != '</script>')) {
			$value .= $token['value'];
			$token = next($tokens);
		}
		prev($tokens);
		if ($value) {
			$this->content = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['js'] && $this->content) {
			$this->content = call_user_func($config['js'], $this->content);
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
