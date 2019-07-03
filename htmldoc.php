<?php
namespace hexydec\html;

require(__DIR__.'/tokens/doctype.php');
require(__DIR__.'/tokens/tag.php');
require(__DIR__.'/tokens/text.php');
require(__DIR__.'/tokens/comment.php');
require(__DIR__.'/tokens/cdata.php');
require(__DIR__.'/tokens/style.php');
require(__DIR__.'/tokens/script.php');

class htmldoc implements \ArrayAccess {

	protected $tokens = Array(
		'doctype' => '<!DOCTYPE',
		'comment' => '<!--[\d\D]*?-->',
		'cdata' => '<!\[CDATA\[[\d\D]*?\]\]>',
		'tagopenstart' => '<[^ >\/]++',
		'tagselfclose' => '\/>',
		'tagopenend' => '>',
		'tagclose' => '<\/[^ >]++>',
		'textnode' => '(?<=>)[^<]++(?=<)',
		'attributevalue' => '=\s*+["\']?[^"\']*+["\']?',
		'attribute' => '[^<>"=\s]++',
		'whitespace' => '\s++'
	);
	protected $selectors = Array(
		'quotes' => '(?<!\\\\)"(?:[^"\\\\]++|\\\\.)*+"',
		'join' => '\s*[>+~]\s*',
		'comparison' => '[\^*$<>]?=', // comparison operators for media queries or attribute selectors
		'squareopen' => '\[',
		'squareclose' => '\]',
		'bracketopen' => '\(',
		'bracketclose' => '\)',
		'comma' => ',',
		'colon' => ':',
		'id' => '#[^ +>\.#{\[]++',
		'class' => '\.[^ +>\.#{\[]++',
		'string' => '!?[^\[\]{}\(\):;,>+=~\^$!" #\.]++',
		'whitespace' => '\s++',
	);
	protected $config = Array(
		'elements' => Array(
			'inline' => Array(
				'b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
			),
			'singleton' => Array(
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			),
			'unnestable' => Array(
				'head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
			),
			'pre' => Array('textarea', 'pre', 'code'), // which elements not to strip whitespace from
			'custom' => Array('script', 'style'), // which elements have their own plugins
		),
		'attributes' => Array(
			'boolean' => Array(
				'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch'
			),
			'default' => Array( // default attributes that can be removed
				'style' => Array(
					'type' => 'text/css'
				),
				'script' => Array(
					'type' => 'text/javascript',
					'language' => true
				),
				'form' => Array(
					'method' => 'get'
				),
				'input' => Array(
					'type' => 'text'
				)
			),
			'empty' => Array('id', 'class', 'style', 'title'), // attributes to remove if empty
			'urls' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
		),
		'minify' => Array(
			'css' => 'hexydec\\minify\\cssmin::minify', // minify CSS
			'js' => false, // minify javascript
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => Array( // remove comments
				'ie' => true
			),
			'urls' => Array( // update internal URL's to be shorter
				'absolute' => true, // process absolute URLs to make them relative to the current document
				'host' => true, // remove the host for own domain
				'scheme' => true // remove the scheme from URLs that have the same scheme as the current document
			),
			'attributes' => Array( // minify attributes
				'default' => true, // remove default attributes
				'empty' => true, // remove these attributes if empty
				'option' => true, // remove value attribute from option where the text node has the same value
				'style' => true, // minify the style tag
				'class' => true, // sort classes
				'sort' => true, // sort attributes for better gzip
				'boolean' => true, // minify boolean attributes
			),
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // minify attribute quotes
		),
		'output' => Array(
			'charset' => null,
			'quotestyle' => 'double', // double, single, minimal
			'singletonclose' => ' />'
		)
	);
	protected $document = Array();
	protected $attributes = Array();

	public function __construct(Array $config = Array()) {
		$this->config = array_replace_recursive($this->config, $config);
	}

	/**
	 * Retrieves the configuration of the object as an array
	 */
	public function toArray() {
		return $this->document;
	}

	/**
	 * Array access method allows you to set the object's configuration as properties
	 *
	 * @param mixed $i The key to be updated, can be a string or integer
	 * @param mixed $value The value of the array key in the configuration array to be updated
	 */
	public function offsetSet($i, $value) {
		if (is_null($i)) $this->document[] = $value;
		else $this->document[$i] = $value;
	}

	/**
	 * Array access method allows you to check that a key exists in the configuration array
	 *
	 * @param mixed $i The key to be checked, can be a string or integer
	 * @return bool Whether the key exists in the config array
	 */
	public function offsetExists($i) {
		return isset($this->document[$i]);
	}

	/**
	 * Removes a key from the configuration array
	 *
	 * @param mixed $i The key to be removed, can be a string or integer
	 */
	public function offsetUnset($i) {
		unset($this->document[$i]);
	}

	/**
	 * Retrieves a value from the configuration array with the specified key
	 *
	 * @param mixed $i The key to be accessed, can be a string or integer
	 * @return mixed The requested value or null if the key doesn't exist
	 */
	public function &offsetGet($i) { // return reference so you can set it like an array
		if (!isset($this->document[$i])) {
			$null = null;
			return $null;
		} else {
			return $this->document[$i];
		}
	}

	public function open(String $url, String &$error = null) {
		if (($handle = fopen($url, 'rb')) === false) {
			$error = 'Could not open file';
		} elseif (($html = stream_get_contents($handle)) === false) {
			$error = 'Could not read file';
		} else {

			// find charset in headers
			$charset = null;
			$meta = stream_get_meta_data($handle);
			if (!empty($meta['wrapper_data'])) {
				foreach ($meta['wrapper_data'] AS $item) {
					if (stripos($item, 'Content-Type:') === 0 && ($charset = stristr($item, 'charset=')) !== false) {
						$charset = substr($charset, 8);
						break;
					}
				}
			}

			// load htmk
			return $this->load($html, $charset);
		}
		return false;
	}

	public function load(string $html, string $charset = null) {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== null) {
			$html = iconv($charset, mb_internal_encoding(), $html);
		}

		// reset the document
		$this->document = Array();

		// tokenise the input HTML
		if (($tokens = $this->tokenise($html, $this->tokens)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);

		// parse the document
		} elseif (!$this->parse($tokens)) {
			trigger_error('Input is not invalid', E_USER_WARNING);

		// success
		} else {
			return true;
		}
		return false;
	}

	protected function getCharsetFromHtml(string $html) : string {
		if (preg_match('/<meta[^>]+charset[^>]+>/i', $html, $match)) {
			$obj = new htmldoc();
			if ($obj->load($match[0], mb_internal_encoding()) && ($value = $obj[0]->attr('content')) !== null && ($charset = stristr($value, 'charset=')) !== false) {
				return substr($charset, 8);
			}
		} else {
			return mb_detect_encoding($html);
		}
	}

	protected function tokenise($code, $patterns) {

		// prepare regexp and extract strings
		$re = '/('.implode(')|(', $patterns).')/u';
		if (preg_match_all($re, $code, $match)) {

			// build tokens into types
			$tokens = Array();
			$keys = array_keys($patterns);
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
			$this->document[] = $item;

		// certain tags have thier own plugins
		} elseif (in_array($parenttag, $this->config['elements']['custom'])) {
			$class = '\\hexydec\\html\\'.$parenttag;
			$item = new $class($this->config);
			$item->parse($tokens);
			$this->document[] = $item;

		// parse children
		} elseif (!in_array($parenttag, $this->config['elements']['singleton'])) {
			$tag = null;
			$token = current($tokens);
			do {
				switch ($token['type']) {
					case 'doctype':
						$item = new doctype();
						$item->parse($tokens);
						if (empty($this->document)) { // only add if found at the top of the document
							$this->document[] = $item;
						}
						break;

					case 'tagopenstart':
						$tag = trim($token['value'], '<');

						// parse the tag
						$item = new tag($tag, $this->config);
						$item->parse($tokens, $attach);
						$this->document[] = $item;
						if ($attach) {
							$this->document[] = $attach;
							$attach = null;
						}
						break;

					case 'tagclose':
						$close = trim($token['value'], '</>');
						if (strtolower($close) != strtolower($tag)) { // if tags not the same, go back to previous level

							// if a tag isn't closed and we are closing a tag that isn't the parent, send the last child tag to the parent level
							if ($tag && $parenttag != $close && get_class(end($this->document)) == 'hexydec\\html\\tag') {
								$attach = array_pop($this->document);
							}
							prev($tokens); // close the tag on each level below until we find itself
							break 2;
						}
						break;

					case 'textnode':
						$item = new text($this->config);
						$item->parse($tokens);
						$this->document[] = $item;
						break;

					case 'cdata':
						$item = new cdata();
						$item->parse($tokens);
						$this->document[] = $item;
						break;

					case 'comment':
						$item = new comment();
						$item->parse($tokens);
						$this->document[] = $item;
						break;
				}
			} while (($token = next($tokens)) !== false);
		}
		return !!$this->document;
	}

	public function find($selector) {
		$found = Array();
		if (is_array($selector) || ($selector = $this->parseSelector($selector)) !== false) {
			foreach ($this->document AS $item) {
				if (get_class($item) == 'hexydec\\html\\tag') {
					foreach ($selector AS $value) {
						if (($items = $item->find($value)) !== false) {
							$found = array_merge($found, $items);
						}
					}
				}
			}
		}
		return $found ? $found : false;
		//return $this->document->find($selector);
	}

	protected function parseSelector(String $selector) {
		$selector = trim($selector);
		if (($tokens = $this->tokenise($selector, $this->selectors)) !== false) {
			$selectors = $parts = Array();
			$join = false;
			$token = current($tokens);
			do {
				switch ($token['type']) {
					case 'id':
						$parts[] = Array(
							'id' => substr($token['value'], 1),
							'join' => $join
						);
						$join = false;
						break;
					case 'class':
						$parts[] = Array(
							'class' => substr($token['value'], 1),
							'join' => $join
						);
						$join = false;
						break;
					case 'string':
						$parts[] = Array(
							'tag' => $token['value'],
							'join' => $join
						);
						$join = false;
						break;
					case 'squareopen':
						$item = Array('join' => $join);
						while (($token = next($tokens)) !== false) {
							if ($token['type'] == 'squareclose') {
								break;
							} elseif ($token['type'] == 'string') {
								$item[isset($item['attribute']) ? 'value' : 'attribute'] = $token['value'];
							} elseif ($token['type'] == 'comparison') {
								$item['comparison'] = $token['value'];
							}
						}
						$parts[] = $item;
						$join = false;
						break;
					case 'join':
						$join = trim($token['value']);
						break;
					case 'whitespace':
						if ($parts) {
							$join = ' ';
						}
						break;
					// case 'colon':
					// 	$parts = ':';
					// 	while (($token = next($tokens)) !== false) {
					// 		if (!in_array($token['type'], Array('whitespace', 'comma', 'curlyopen'))) {
					// 			$parts .= $token['value'];
					// 		} else {
					// 			prev($tokens);
					// 			break;
					// 		}
					// 	}
					// 	$selector[] = Array(
					// 		'selector' => $parts,
					// 		'join' => $join
					// 	);
					// 	$join = false;
					// 	break;
					case 'comma':
						$selectors[] = $parts;
						$parts = Array();
						break;
					case 'curlyopen':
						$selectors[] = $parts;
						$parts = Array();
						break 2;
				}
			} while (($token = next($tokens)) !== false);
			if ($parts) {
				$selectors[] = $parts;
			}
			return $selectors;
		}
		return false;
	}

	public function children() : Array {
		return $this->document;
	}

	public function minify(Array $config = Array(), tag $parent = null) {

		// merge config
		$config = array_replace_recursive($this->config['minify'], $config);

		if (!$parent) {
			$parent = $this;
		}

		// set minify output parameters
		if ($config['singleton']) {
			$this->config['output']['singletonclose'] = '>';
		}
		if ($config['quotes']) {
			$this->config['output']['quotestyle'] = 'minimal';
		}

		// sort attributes
		// if ($config['attributes']['sort']) {
		// 	arsort($this->attributes, SORT_NUMERIC);
		// 	$config['attributes']['sort'] = \array_keys($this->attributes);
		// }
		foreach ($this->document AS $item) {
			$item->minify($config, $parent);
		}
	}

	public function compile(Array $options) : String {
		$options = array_merge($this->config['output'], $options);
		$html = '';
		foreach ($this->document AS $item) {
			$html .= $item->compile($options);
		}
		return $html;
	}

	public function save(string $file = null, Array $options = Array()) {

		// compile html
		$html = $this->compile($options);

		// convert charset
		if (!empty($options['charset'])) {
			$html = iconv(mb_internal_encoding(), $options['charset'], $html);
		}

		// save file
		if (!$file) {
			return $html;
		} elseif (file_put_contents($file, $html) === false) {
			trigger_error('File could not be written', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}
}
