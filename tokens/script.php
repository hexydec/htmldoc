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
		$quotes = 0;
		while ($token !== false && ($quotes % 2 || $token['type'] != 'tagclose' || $token['value'] != '</script>')) {
			$value .= $token['value'];

			// count quotes so we don't capture a script tag in a string
			$quotes += substr_count(str_replace(['\\\\', '\\\\"', "\\\\'"], ['', '', ''], $token['value']), '"');
			$token = next($tokens);
		}
		prev($tokens);
		if ($value) {
			$this->content = $value;
		}
	}

	public function minify(Array $config) {
		if ($config['js'] && $this->content) {
			$this->content = $config['js'] === true ? trim($this->content) : call_user_func($config['js'], $this->content);
		}
	}

	public function compile(Array $config) {
		return $this->content;
	}

	public function __get($var) {
		return $this->$var;
	}
}
