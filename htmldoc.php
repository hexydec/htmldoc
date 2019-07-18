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

	/**
	 * @var Array $token Regexp components keyed by their corresponding codename for tokenising HTML
	 */
	protected $tokens = Array(
		'doctype' => '<!DOCTYPE',
		'comment' => '<!--[\d\D]*?-->',
		'cdata' => '<!\[CDATA\[[\d\D]*?\]\]>',
		'tagopenstart' => '<[^ >\/]++',
		'tagselfclose' => '\/>',
		'tagopenend' => '>',
		'tagclose' => '<\/[^ >]++>',
		'textnode' => '(?<=>)[^<]++(?=<)',
		'attributevalue' => '=\s*+(?:"[^"]*+"|\'[^\']*+\'|[^ >]*+)',
		'attribute' => '[^<>"=\s]++',
		'whitespace' => '\s++'
	);

	/**
	 * @var Array $selectors Regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
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
		'string' => '[^\[\]{}\(\):;,>+=~\^$!" #\.*]++',
		'whitespace' => '\s++',
	);

	/**
	 * @var Array $config Object configuration array
	 */
	protected $config = Array(
		'elements' => Array(
			'inline' => Array(
				'b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
			),
			'singleton' => Array(
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			),
			'closeoptional' => Array(
				'html', 'head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup'
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
					'type' => 'text/css',
					'media' => 'all'
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
			'empty' => Array('id', 'class', 'style', 'title', 'lang', 'dir', 'onfocus', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup'), // attributes to remove if empty
			'urls' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
		),
		'minify' => Array(
			'css' => 'hexydec\\html\\cssmin::minify', // minify CSS
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
			'close' => true // don't write close tags where possible
		),
		'output' => Array(
			'charset' => null, // set the output charset
			'quotestyle' => 'double', // double, single, minimal
			'singletonclose' => ' />', // string to close singleton tags
			'closetags' => false // whether to force tags to have a closing tag (true) or follow tag::close
		)
	);

	/**
	 * @var Array $children Stores the regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected $children = Array();
	//protected $attributes = Array();

	/**
	 * Constructs the object
	 *
	 * @param Array $config An array of configuration parameters that is recursively merged with the default config
	 */
	public function __construct(Array $config = Array()) {
		$this->config = array_replace_recursive($this->config, $config);
	}

	/**
	 * Retrieves the configuration of the object as an array
	 *
	 * @return Array An array of child nodes
	 */
	public function toArray() {
		return $this->children;
	}

	/**
	 * Array access method allows you to set the object's configuration as properties
	 *
	 * @param mixed $i The key to be updated, can be a string or integer
	 * @param mixed $value The value of the array key in the configuration array to be updated
	 */
	public function offsetSet($i, $value) {
		if (is_null($i)) $this->children[] = $value;
		else $this->children[$i] = $value;
	}

	/**
	 * Array access method allows you to check that a key exists in the configuration array
	 *
	 * @param mixed $i The key to be checked, can be a string or integer
	 * @return bool Whether the key exists in the config array
	 */
	public function offsetExists($i) {
		return isset($this->children[$i]);
	}

	/**
	 * Removes a key from the configuration array
	 *
	 * @param mixed $i The key to be removed, can be a string or integer
	 */
	public function offsetUnset($i) {
		unset($this->children[$i]);
	}

	/**
	 * Retrieves a value from the configuration array with the specified key
	 *
	 * @param mixed $i The key to be accessed, can be a string or integer
	 * @return mixed The requested value or null if the key doesn't exist
	 */
	public function &offsetGet($i) { // return reference so you can set it like an array
		if (!isset($this->children[$i])) {
			$null = null;
			return $null;
		} else {
			return $this->children[$i];
		}
	}

	public function getConfig() {
		$config = $this->config;
		foreach (func_get_args() AS $item) {
			if (isset($config[$item])) {
				$config = $config[$item];
			} else {
				return null;
			}
		}
		return $config;
	}

	/**
	 * Open an HTML file from a URL
	 *
	 * @param string $url The address of the HTML file to retrieve
	 * @param string $context An optional array of context parameters
	 * @return mixed The loaded HTML, or false on error
	 */
	public function open(String $url, Resource $context = null, String &$error = null) {
		if (($handle = fopen($url, 'rb', $context)) === false) {
			$error = 'Could not open file "'.$url.'"';
		} elseif (($html = stream_get_contents($handle)) === false) {
			$error = 'Could not read file "'.$url.'"';
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
			if ($this->load($html, $charset, $error)) {
				return $html;
			}
		}
		return false;
	}

	/**
	 * Parse an HTML string into the object
	 *
	 * @param string $html A string containing valid HTML
	 * @param string $charset The charset of the document
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $html, string $charset = null, &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== false || ($charset = mb_detect_encoding($html)) !== false) {
			$html = mb_convert_encoding($html, $charset, mb_internal_encoding());
		}

		// reset the document
		$this->children = Array();

		// tokenise the input HTML
		if (($tokens = tokenise::tokenise($html, $this->tokens)) === false) {
			$error = 'Could not tokenise input';

		// parse the document
		} elseif (!$this->parse($tokens)) {
			$error = 'Input is not invalid';

		// success
		} else {
			// \var_dump($this->debug());
			return true;
		}
		return false;
	}

	/**
	 * Reads the charset defined in the Content-Type meta tag, or detects the charset from the HTML content
	 *
	 * @param string $html A string containing valid HTML
	 * @return string The defined or detected charset or false if the charset is not defined
	 */
	protected function getCharsetFromHtml(string $html) {
		if (preg_match('/<meta[^>]+charset[^>]+>/i', $html, $match)) {
			$obj = new htmldoc();
			if ($obj->load($match[0], mb_internal_encoding()) && ($value = $obj[0]->attr('content')) !== null && ($charset = stristr($value, 'charset=')) !== false) {
				return substr($charset, 8);
			}
		}
		return false;
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param Array &$tokens An array of tokens generated by tokenise()
	 * @param String $parenttag The tag name of the parent tag, or null if there is no parent
	 * @return bool Whether the parser was able to capture any objects
	 */
	public function parse(Array &$tokens, String $parenttag = null) : bool {

		// process custom tags
		if (in_array($parenttag, $this->config['elements']['custom'])) {
			$class = '\\hexydec\\html\\'.$parenttag;
			$item = new $class($this->config);
			$item->parse($tokens);
			$this->children[] = $item;

		// parse children
		} elseif (!in_array($parenttag, $this->config['elements']['singleton'])) {
			$tag = null;
			$token = current($tokens);
			do {
				switch ($token['type']) {
					case 'doctype':
						$item = new doctype();
						$item->parse($tokens);
						if (empty($this->children)) { // only add if found at the top of the document
							$this->children[] = $item;
						}
						break;

					case 'tagopenstart':
						$tag = trim($token['value'], '<');
						if ($tag == $parenttag && in_array($tag, $this->config['elements']['closeoptional'])) {
							prev($tokens);
							break 2;
						} else {

							// parse the tag
							$item = new tag($tag, $this->config);
							$item->parse($tokens);
							$this->children[] = $item;
							if (in_array($tag, $this->config['elements']['singleton'])) {
								$tag = null;
							}
						}
						break;

					case 'tagclose':
						prev($tokens); // close the tag on each level below until we find itself
						break 2;

					case 'textnode':
						$item = new text($this->config);
						$item->parse($tokens);
						$this->children[] = $item;
						break;

					case 'cdata':
						$item = new cdata();
						$item->parse($tokens);
						$this->children[] = $item;
						break;

					case 'comment':
						$item = new comment();
						$item->parse($tokens);
						$this->children[] = $item;
						break;
				}
			} while (($token = next($tokens)) !== false);
		}
		return !!$this->children;
	}

	protected function parseSelector(String $selector) {
		$selector = trim($selector);
		if (($tokens = tokenise::tokenise($selector, $this->selectors)) !== false) {
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

	public function find($selector) {
		$found = Array();

		// parse selector and find tags
		if (is_array($selector) || ($selector = $this->parseSelector($selector)) !== false) {
			foreach ($this->children AS $item) {
				if (get_class($item) == 'hexydec\\html\\tag') {
					foreach ($selector AS $value) {
						if (($items = $item->find($value)) !== false) {
							$found = array_merge($found, $items);
						}
					}
				}
			}
		}

		// create new document and return
		$doc = new htmldoc($this->config);
		if ($found) {
			$doc->collection($found);
		}
		return $doc;
	}

	public function first() : htmldoc {
		return $this->eq(0);
	}

	public function last() : htmldoc {
		return $this->eq(-1);
	}

	public function eq(int $index) : htmldoc {
		$doc = new htmldoc($this->config);
		if ($index < 0) {
			$index = count($this->children) + $index;
		}
		if (isset($this->children[$index])) {
			$doc->collection(Array($this->children[$index]));
		}
		return $doc;
	}

	public function children() : htmldoc {
		return $this->find('>*');
	}

	public function attr(string $key) {
		foreach ($this->children AS $item) {
			if (get_class($item) == 'hexydec\\html\\tag') {
				return $item->attr($key);
			}
		}
	}

	public function text() : string {
		$text = '';
		foreach ($this->children AS $item) {

			// only get text from these objects
			if (in_array(get_class($item), Array('hexydec\\html\\tag', 'hexydec\\html\\text', 'hexydec\\html\\pre'))) {
				$text .= $item->text();

				// add a space to make sure words aren't joined
				if ($text && mb_substr($text, -1) != ' ') {
					$text .= ' ';
				}
			}
		}
		return $text;
	}

	public function collection(Array $nodes) {
		$this->children = $nodes;
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
		foreach ($this->children AS $item) {
			$item->minify($config, $parent);
		}
	}

	public function compile(Array $options = null) : String {
		$options = $options ? array_merge($this->config['output'], $options) : $this->config['output'];
		$html = '';
		foreach ($this->children AS $item) {
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

	public function debug() {
		$output = Array();
		foreach ($this->children AS $item) {
			$node = Array(
				'type' => get_class($item)
			);
			switch ($node['type']) {
				case 'hexydec\\html\\tag':
					$node['tag'] = $item->tagName;
					$node['attributes'] = $item->attributes;
					$node['singleton'] = $item->singleton;
					$node['close'] = $item->close;
					if ($item->children) {
						$node['children'] = $item->children->debug();
					}
					break;
				case 'hexydec\\html\\doctype':
					$node['doctype'] = $item->type;
					break;
				default:
					$node['content'] = $item->content;
					break;
			}
			$output[] = $node;
		}
		return $output;
	}
}
