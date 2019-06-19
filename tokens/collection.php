<?php
namespace hexydec\html;

class collection implements \ArrayAccess {

	protected $config = Array();
	protected $collection = Array();

	public function __construct($config) {
		$this->config = $config;
	}

	/**
	 * Retrieves the configuration of the object as an array
	 */
	public function toArray() {
		return $this->collection;
	}

	/**
	 * Array access method allows you to set the object's configuration as properties
	 *
	 * @param mixed $i The key to be updated, can be a string or integer
	 * @param mixed $value The value of the array key in the configuration array to be updated
	 */
	public function offsetSet($i, $value) {
		if (is_null($i)) $this->collection[] = $value;
		else $this->collection[$i] = $value;
	}

	/**
	 * Array access method allows you to check that a key exists in the configuration array
	 *
	 * @param mixed $i The key to be checked, can be a string or integer
	 * @return bool Whether the key exists in the config array
	 */
	public function offsetExists($i) {
		return isset($this->collection[$i]);
	}

	/**
	 * Removes a key from the configuration array
	 *
	 * @param mixed $i The key to be removed, can be a string or integer
	 */
	public function offsetUnset($i) {
		unset($this->collection[$i]);
	}

	/**
	 * Retrieves a value from the configuration array with the specified key
	 *
	 * @param mixed $i The key to be accessed, can be a string or integer
	 * @return mixed The requested value or null if the key doesn't exist
	 */
	public function &offsetGet($i) { // return reference so you can set it like an array
		if (!isset($this->collection[$i])) {
			$null = null;
			return $null;
		} else {
			return $this->collection[$i];
		}
	}

	public function load(string $html) {
		if (($tokens = $this->tokenise($html, $this->config)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);
		} elseif (($ast = $this->parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);
		} else {
			return $ast;
		}
		return false;
	}

	protected function tokenise($code, $config) {

		// prepare regexp and extract strings
		$re = '/('.implode(')|(', $config['tokens']).')/';
		if (preg_match_all($re, $code, $match)) {

			// build tokens into types
			$tokens = Array();
			$keys = array_keys($config['tokens']);
			foreach ($match[0] AS $i => $item) {

				// go through tokens and find which one matched
				foreach ($keys AS $token => $type) {
					if ($match[$token+1][$i] !== '') {
						$tokens[] = Array(
							'type' => $type,
							'value' => $item
						);
						break;
					}
				}
			}
			return $tokens;
		}
		return false;
	}

	public function parse(Array &$tokens, string $parenttag = null, array &$attach = null) : bool {

		// keep whitespace for certain tags
		if (in_array($parenttag, $this->config['elements']['pre'])) {
			$item = new pre();
			$item->parse($tokens);
			$this->collection[] = $item;

		// certain tags have thier own plugins
		} elseif (in_array($parenttag, $this->config['elements']['custom'])) {
			$class = '\\hexydec\\html\\'.$parenttag;
			$item = new $class($this->config);
			$item->parse($tokens);
			$this->collection[] = $item;

		// parse children
		} elseif (!in_array($parenttag, $this->config['elements']['singleton'])) {
			$tag = null;
			$token = current($tokens);
			do {
				switch ($token['type']) {
					case 'doctype':
						$item = new doctype();
						$item->parse($tokens);
						if (empty($ast)) { // only add if found at the top of the document
							$this->collection[] = $item;
						}
						break;

					case 'tagopenstart':
						$tag = strtolower(trim($token['value'], '<'));

						// parse the tag
						$item = new tag($tag, $this->config);
						$item->parse($tokens, $attach);
						$this->collection[] = $item;
						if ($attach) {
							$this->collection[] = $attach;
							$attach = null;
						}
						break;

					case 'tagclose':
						$close = strtolower(trim($token['value'], '</>'));
						if ($close != $tag) { // if tags not the same, go back to previous level

							// if a tag isn't closed and we are closing a tag that isn't the parent, send the last child tag to the parent level
							if ($tag && $parenttag != $close && get_class(end($this->collection)) == 'hexydec\\html\\tag') {
								$attach = array_pop($this->collection);
							}
							prev($tokens); // close the tag on each level below until we find itself
							break 2;
						}
						break;

					case 'textnode':
						$item = new text($this->config);
						$item->parse($tokens);
						$this->collection[] = $item;
						break;

					case 'cdata':
						$item = new cdata();
						$item->parse($tokens);
						$this->collection[] = $item;
						break;

					case 'comment':
						$item = new comment();
						$item->parse($tokens);
						$this->collection[] = $item;
						break;
				}
			} while (($token = next($tokens)) !== false);
		}
		return true;
	}

	public function children() {
		return $this;
	}

	public function minify($config, $parent = null) {
		foreach ($this->collection AS $item) {
			$item->minify($config, $parent);
		}
	}

	public function compile($config) : string {
		$singleton = $this->config['elements']['singleton'];
		$html = '';
		foreach ($this->collection AS $item) {
			$html .= $item->compile($config);
		}
		return $html;
	}
}
