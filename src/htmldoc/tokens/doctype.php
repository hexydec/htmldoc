<?php
namespace hexydec\html;

class doctype {

	protected $content = null;

	public function parse(array &$tokens) {
		$content = '';
		while (($token = next($tokens)) !== false && $token['type'] != 'tagopenend') {
			if ($token['type'] == 'attribute') {
				$content .= ($content ? ' ' : '').$token['value'];
			}
		}
		$this->content = html_entity_decode($content);
	}

	public function minify() {

	}

	public function html() {
		return '<!DOCTYPE '.\htmlspecialchars($this->content).'>';
	}

	public function __get($var) {
		return $this->$var;
	}
}
