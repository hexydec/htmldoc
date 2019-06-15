<?php
namespace hexydec\html;

class ast {

	protected $config = Array();

	public function __construct($config) {
		$this->config = $config;
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

	public function parse(Array &$tokens, string $parenttag = null, array &$attach = null) : Array {
		$ast = Array();
		$tag = null;
		$token = current($tokens);
		do {
			switch ($token['type']) {
				case 'doctype':
					$item = new doctype();
					$item->parse($tokens);
					if (empty($ast)) { // only add if found at the top of the document
						$ast[] = $item;
					}
					break;

				case 'tagopenstart':
					$tag = strtolower(trim($token['value'], '<'));

					// parse the tag
					$item = new tag($tag, $this->config);
					$item->parse($tokens, $attach);
					$ast[] = $item;
					if ($attach) {
						$ast[] = $attach;
						$attach = null;
					}
					break;

				case 'tagclose':
					$close = strtolower(trim($token['value'], '</>'));
					if ($close != $tag) { // if tags not the same, go back to previous level

						// if a tag isn't closed and we are closing a tag that isn't the parent, send the last child tag to the parent level
						if ($tag && $parenttag != $close && get_class(end($ast)) == 'hexydec\\html\\tag') {
							$attach = array_pop($ast);
						}
						prev($tokens); // close the tag on each level below until we find itself
						break 2;
					}
					break;

				case 'textnode':
					$item = new text();
					$item->parse($tokens);
					$ast[] = $item;
					break;

				case 'cdata':
					$item = new cdata();
					$item->parse($tokens);
					$ast[] = $item;
					break;

				case 'comment':
					$item = new comment();
					$item->parse($tokens);
					$ast[] = $item;
					break;
			}
		} while (($token = next($tokens)) !== false);
		return $ast;
	}
}
