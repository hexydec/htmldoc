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

	/**
	 * Minifies the internal representation of the doctype
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify() {

	}

	public function html() : string {
		return '<!DOCTYPE '.\htmlspecialchars($this->content).'>';
	}

	public function __get($var) {
		return $this->$var;
	}
}
