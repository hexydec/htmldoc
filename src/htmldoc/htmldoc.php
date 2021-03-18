<?php
declare(strict_types = 1);
namespace hexydec\html;

class htmldoc implements \ArrayAccess, \Iterator {

	/**
	 * @var array $tokens Regexp components keyed by their corresponding codename for tokenising HTML
	 */
	protected static $tokens = [
		'textnode' => '(?<=>|^)[^<]++(?=<|$)',
		'attributevalue' => '\\s*+=\\s*+(?:"[^"]*+"|\'[^\']*+\'|[^\\s>]*+)',
		'attribute' => '\\s*+[^<>"\'\\/=\\s]++',
		'tagopenend' => '\\s*+>',
		'tagselfclose' => '\\s*+\\/>',
		'tagopenstart' => '<[a-zA-Z][a-zA-Z0-9_:.-]*+',
		'tagclose' => '<\\/[a-zA-Z][a-zA-Z0-9_:.-]*+\\s*+>',
		'doctype' => '<!(?i:DOCTYPE)',
		'comment' => '<!--[\\d\\D]*?(?<=--)>',
		'cdata' => '<!\\[CDATA\\[[\\d\\D]*?\\]\\]>',
		'other' => '.'
	];

	/**
	 * @var array $selectors Regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected static $selectors = [
		'quotes' => '(?<!\\\\)"(?:[^"\\\\]++|\\\\.)*+"',
		'join' => '\s*[>+~]\s*',
		'comparison' => '[\^*$<>]?=', // comparison operators for media queries or attribute selectors
		'squareopen' => '\[',
		'squareclose' => '\]',
		'bracketopen' => '\(',
		'bracketclose' => '\)',
		'comma' => ',',
		'pseudo' => ':[A-Za-z-]++',
		'id' => '#[^ +>\.#{\[,]++',
		'class' => '\.[^ +>\.#{\[,]++',
		'string' => '\*|[^\[\]{}\(\):;,>+=~\^$!" #\.*]++',
		'whitespace' => '\s++',
	];

	/**
	 * @var array $config Object configuration array
	 */
	protected $config = [
		'elements' => [
			'inline' => [
				'b', 'u', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
			],
			'singleton' => [
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			],
			'closeoptional' => [
				'html', 'head', 'body', 'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup'
			]
		],
		'attributes' => [
			'boolean' => [
				'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay', 'checked', 'contenteditable', 'controls', 'default', 'defer', 'disabled', 'formnovalidate', 'hidden', 'indeterminate', 'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate', 'open', 'readonly', 'required', 'reversed', 'scoped', 'selected', 'typemustmatch'
			],
			'default' => [ // default attributes that can be removed
				'style' => [
					'type' => 'text/css',
					'media' => 'all'
				],
				'script' => [
					'type' => 'text/javascript',
					'language' => true
				],
				'form' => [
					'method' => 'get'
				],
				'input' => [
					'type' => 'text'
				]
			],
			'empty' => ['id', 'class', 'style', 'title', 'action', 'alt', 'lang', 'dir', 'onfocus', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup'], // attributes to remove if empty
			'urls' => ['href', 'src', 'action', 'poster'], // attributes to minify URLs in
			'urlskip' => [
				'link' => [
					'rel' => ['stylesheet', 'icon', 'shortcut icon', 'apple-touch-icon-precomposed', 'apple-touch-icon', 'preload', 'prefetch', 'author', 'help']
				]
			]
		],
		'custom' => [ // specify custom handlers
			'style' => [
				'class' => '\\hexydec\\html\\style',
				'config' => [
					'minifier' => null
				]
			],
			'script' => [
				'class' => '\\hexydec\\html\\script',
				'config' => [
					'minifier' => null
				]
			]
		],
		'minify' => [
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => [
				'remove' => true, // remove comments
				'ie' => true // preserve IE comments
			],
			'urls' => [ // update internal URL's to be shorter
				'scheme' => true, // remove the scheme from URLs that have the same scheme as the current document
				'host' => true, // remove the host for own domain
				'relative' => true, // process absolute URLs to make them relative to the current document
				'parent' => true // process relative URLs to use relative parent links where it is shorter
			],
			'elements' => [ // apply specific minifier options to certain tag trees
				'textarea' => ['whitespace' => false],
				'pre' => ['whitespace' => false],
				'code' => ['whitespace' => false],
				'svg' => [
					'lowercase' => false,
					'attributes' => [
						'default' => false,
						'empty' => false,
						'option' => false,
						'boolean' => false
					],
					'singleton' => false,
					'close' => false
				]
			],
			'attributes' => [ // minify attributes
				'default' => true, // remove default attributes
				'empty' => true, // remove these attributes if empty
				'option' => true, // remove value attribute from option where the text node has the same value
				'style' => true, // minify the style tag
				'class' => true, // sort classes
				'sort' => true, // sort attributes for better gzip
				'boolean' => true // minify boolean attributes
			],
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // sets the output option 'quotestyle' to 'minimal'
			'close' => true, // don't write close tags where possible
			'email' => false, // sets the minification presets to email safe options
			'style' => [], // specify CSS minifier options
			'script' => [] // specify CSS javascript options
		]
	];

	/**
	 * @var array Contains the output settings
	 */
	protected $output = [
		'charset' => null, // set the output charset
		'quotestyle' => 'double', // double, single, minimal
		'singletonclose' => null, // string to close singleton tags, or false to leave as is
		'closetags' => false, // whether to force tags to have a closing tag (true) or follow tag::close
		'xml' => false, // sets the output presets to produce XML valid code
		'elements' => [ // output options for particular tags elements
			'svg' => [
				'xml' => true,
				'quotestyle' => 'double', // double, single, minimal
				'singletonclose' => '/>', // string to close singleton tags, or false to leave as is
				'closetags' => true, // whether to force tags to have a closing tag (true) or follow tag::close
			]
		]
	];

	/**
	 * @var array $children Stores the regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected $children = [];

	/**
	 * @var int $pointer The current pointer position for the array iterator
	 */
	protected $pointer = 0;

	/**
	 * @var array A cache of attribute and class names for sorting
	 */
	protected $cache = [];

	/**
	 * Constructs the object
	 *
	 * @param array $config An array of configuration parameters that is recursively merged with the default config
	 */
	public function __construct(array $config = []) {
		$this->config = array_replace_recursive($this->config, [
			'custom' => [

				// default to CSSdoc if available
				'style' => [
					'config' => [
						'minifier' => class_exists('\\hexydec\\css\\cssdoc') ? function (string $css, array $minify) {
							$obj = new \hexydec\css\cssdoc();
							if ($obj->load($css)) {
								$obj->minify($minify);
								return $obj->compile();
							}
							return $css;
						} : null
					]
				],

				// default to JSLite if available
				'script' => [
					'config' => [
						'minifier' => class_exists('\\hexydec\\jslite\\jslite') ? function (string $css, array $minify) {
							$obj = new \hexydec\jslite\jslite();
							if ($obj->load($css)) {
								$obj->minify($minify);
								return $obj->compile();
							}
							return $css;
						} : null
					]
				]
			]
		], $config);
	}

	/**
	 * Calculates the length property
	 *
	 * @param string $var The name of the property to retrieve, currently 'length' and output
	 * @return mixed The number of children in the object for length, the output config, or null if the parameter doesn't exist
	 */
	public function __get(string $var) {
		if ($var == 'length') {
			return count($this->children);
		} elseif ($var == 'output') {
			return $this->output;
		}
		return null;
	}

	/**
	 * Retrieves the children of the document as an array
	 *
	 * @return array An array of child nodes
	 */
	public function toArray() : array {
		return $this->children;
	}

	/**
	 * Array access method allows you to set the object's configuration as properties
	 *
	 * @param string|integer $i The key to be updated, can be a string or integer
	 * @param mixed $value The value of the array key in the children array to be updated
	 */
	public function offsetSet($i, $value) : void {
		if (is_null($i)) $this->children[] = $value;
		else $this->children[$i] = $value;
	}

	/**
	 * Array access method allows you to check that a key exists in the configuration array
	 *
	 * @param string|integer $i The key to be checked, can be a string or integer
	 * @return bool Whether the key exists in the config array
	 */
	public function offsetExists($i) : bool {
		return isset($this->children[$i]);
	}

	/**
	 * Removes a key from the configuration array
	 *
	 * @param string|integer $i The key to be removed, can be a string or integer
	 */
	public function offsetUnset($i) : void {
		unset($this->children[$i]);
	}

	/**
	 * Retrieves a value from the configuration array with the specified key
	 *
	 * @param string|integer $i The key to be accessed, can be a string or integer
	 * @return mixed The requested value or null if the key doesn't exist
	 */
	public function offsetGet($i) { // return reference so you can set it like an array
		return $this->children[$i] ?? null;
	}

	public function current() {
		return $this->children[$this->pointer] ?? null;
	}

	public function key() : scalar {
		return $this->pointer;
	}

	public function next() : void {
		$this->pointer++;
	}

	public function rewind() : void {
		$this->pointer = 0;
	}

	public function valid() : bool {
		return isset($this->children[$this->pointer]);
	}

	/**
	 * Retrieves the requested value of the object configuration
	 *
	 * @param string ...$key One or more array keys indicating the configuration value to retrieve
	 * @return mixed The value requested, or null if the value doesn't exist
	 */
	public function getConfig(string ...$keys) {
		$config = $this->config;
		foreach ($keys AS $item) {
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
	 * @param resource $context An optional array of context parameters
	 * @param string &$error A reference to any user error that is generated
	 * @return mixed The loaded HTML, or false on error
	 */
	public function open(string $url, $context = null, string &$error = null) {

		// open a handle to the stream
		if (($handle = @fopen($url, 'rb', false, $context)) === false) {
			$error = 'Could not open file "'.$url.'"';

		// retrieve the stream contents
		} elseif (($html = stream_get_contents($handle)) === false) {
			$error = 'Could not read file "'.$url.'"';

		// success
		} else {

			// find charset in headers
			$charset = null;
			$meta = stream_get_meta_data($handle);
			if (!empty($meta['wrapper_data'])) {
				foreach ($meta['wrapper_data'] AS $item) {
					if (mb_stripos($item, 'Content-Type:') === 0 && ($charset = mb_stristr($item, 'charset=')) !== false) {
						$charset = mb_substr($charset, 8);
						break;
					}
				}
			}

			// load html
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
	 * @param string &$error A reference to any user error that is generated
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $html, string $charset = null, &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== null) {
			$html = mb_convert_encoding($html, mb_internal_encoding(), $charset);
		}

		// reset the document
		$this->children = [];

		// tokenise the input HTML
		$tokens = new tokenise(self::$tokens, $html);

		// parse the document
		if (!$this->parse($tokens)) {
			$error = 'Input is not valid';

		// success
		} else {
			// var_dump($tokens);
			return true;
		}
		return false;
	}

	/**
	 * Reads the charset defined in the Content-Type meta tag, or detects the charset from the HTML content
	 *
	 * @param string $html A string containing valid HTML
	 * @return string The defined or detected charset or null if the charset is not defined
	 */
	protected function getCharsetFromHtml(string $html) : ?string {
		if (preg_match('/<meta[^>]+charset[^>]+>/i', $html, $match)) {
			$obj = new htmldoc($this->config);
			if ($obj->load($match[0], mb_internal_encoding())) {

				// <meta charset="xxx" />
				if (($value = $obj->attr('charset')) !== null) {
					return $value;

				// <meta http-equiv="Content-Type" content="text/html; charset=xxx" />
				} elseif (($value = $obj->eq(0)->attr('content')) !== null && ($charset = mb_stristr($value, 'charset=')) !== false) {
					return mb_substr($charset, 8);
				}
			}
		} elseif (($charset = mb_detect_encoding($html)) !== false) {
			return $charset;
		}
		return null;
	}

	/**
	 * Parses an array of tokens into an HTML document
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @return bool Whether the parser was able to capture any objects
	 */
	protected function parse(tokenise $tokens) : bool {
		$tag = new tag($this);
		$this->children = $tag->parseChildren($tokens);
		return !!$this->children;
	}

	/**
	 * Parses a CSS selector string
	 *
	 * @param string $selector The CSS seelctor string to parse
	 * @return array An array of selector components
	 */
	protected function parseSelector(string $selector) {
		$selector = trim($selector);
		$tokens = new tokenise(self::$selectors, $selector);
		if (($token = $tokens->current()) !== null) {
			$selectors = $parts = [];
			$join = null;
			do {
				switch ($token['type']) {

					case 'id':
						$parts[] = [
							'id' => mb_substr($token['value'], 1),
							'join' => $join
						];
						$join = null;
						break;

					case 'class':
						$parts[] = [
							'class' => mb_substr($token['value'], 1),
							'join' => $join
						];
						$join = null;
						break;

					case 'string':
						$parts[] = [
							'tag' => $token['value'],
							'join' => $join
						];
						$join = null;
						break;

					case 'squareopen':
						$item = ['join' => $join];
						while (($token = $tokens->next()) !== false) {
							if ($token['type'] == 'squareclose') {
								break;
							} elseif (in_array($token['type'], ['string', 'quotes'])) {
								if ($token['type'] == 'quotes') {
									$token['value'] = stripslashes(mb_substr($token['value'], 1, -1));
								}
								$item[isset($item['attribute']) ? 'value' : 'attribute'] = $token['value'];
							} elseif ($token['type'] == 'comparison') {
								$item['comparison'] = $token['value'];
							}
						}
						$parts[] = $item;
						$join = null;
						break;

					case 'pseudo':
						$parts[] = [
							'pseudo' => mb_substr($token['value'], 1),
							'join' => $join
						];
						$join = null;
						break;

					case 'join':
						$join = trim($token['value']);
						break;

					case 'whitespace':
						if ($parts) {
							$join = ' ';
						}
						break;

					case 'comma':
						$selectors[] = $parts;
						$parts = [];
						break;
				}
			} while (($token = $tokens->next()) !== null);
			if ($parts) {
				$selectors[] = $parts;
			}
			return $selectors;
		}
		return false;
	}

	/**
	 * Caches the input values and records the number of occurences
	 *
	 * @param string $key The key to store the value under
	 * @param array $values An array of values to add to the cache
	 * @return void
	 */
	public function cache(string $key, array $values) : void {

		// initialise cache
		if (!isset($this->cache[$key])) {
			$this->cache[$key] = [];
		}

		// count values
		foreach ($values AS $item) {
			if (!isset($this->cache[$key][$item])) {
				$this->cache[$key][$item] = 1;
			} else {
				$this->cache[$key][$item]++;
			}
		}
	}

	/**
	 * Retrieves the tag object at the specified index, or all children of type tag
	 *
	 * @param int $index The index of the child tag to retrieve
	 * @return mixed A tag object if index is specified, or an array of tag objects, or null if the specified index doesn't exist or the object is empty
	 */
	public function get(int $index = null) {

		// build children that are tags
		$children = [];
		foreach ($this->children AS $item) {
			if (get_class($item) == 'hexydec\\html\\tag') {
				$children[] = $item;
			}
		}

		// return all children if no index
		if ($index === null) {
			return $children;
		}

		// check if index is minus
		if ($index < 0) {
			$index = count($children) + $index;
		}

		// return index if set
		if (isset($children[$index])) {
			return $children[$index];
		}
		return null;
	}

	/**
	 * Find children within the object using a CSS selector
	 *
	 * @param string $selector A CSS selector specifying the children to find
	 * @return htmldoc A new htmldoc object containing the found tag items
	 */
	public function find(string $selector) : htmldoc {
		$found = [];

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

	/**
	 * Retrieves an htmldoc object containing the first tag in the collection
	 *
	 * @return htmldoc A new htmldoc object
	 */
	public function first() : htmldoc {
		return $this->eq(0);
	}

	/**
	 * Retrieves an htmldoc object containing the last tag in the collection
	 *
	 * @return htmldoc A new htmldoc object
	 */
	public function last() : htmldoc {
		return $this->eq(-1);
	}

	/**
	 * Retrieves an htmldoc object containing the tag in the collection at the specificed index
	 *
	 * @param int $index The index position of the tag to retrieve
	 * @return htmldoc A new htmldoc object
	 */
	public function eq(int $index) : htmldoc {
		$doc = new htmldoc($this->config);
		if ($index < 0) {
			$index = count($this->children) + $index;
		}
		if (isset($this->children[$index])) {
			$doc->collection([$this->children[$index]]);
		}
		return $doc;
	}

	/**
	 * Generate a new htmldoc object containing all the child tags of the parents
	 *
	 * @return htmldoc A new htmldoc object
	 */
	public function children() : htmldoc {
		return $this->find('>*');
	}

	/**
	 * Retrieves the specified attribute value from the first tag in the collection
	 *
	 * @param string $key The name of the attribute to retrieve
	 * @return string The value of the attribute or null if the attribute doesn't exist
	 */
	public function attr(string $key) : ?string {
		foreach ($this->children AS $item) {
			if (get_class($item) == 'hexydec\\html\\tag') {
				return $item->attr($key);
			}
		}
		return null;
	}

	/**
	 * Retrievves the value of the text nodes contained within the object, multiple values are concatenated with a space
	 *
	 * @return string The value of the contained text nodes concatenated together with spaces
	 */
	public function text() : string {
		$text = [];
		foreach ($this->children AS $item) {

			// only get text from these objects
			if (in_array(get_class($item), ['hexydec\\html\\tag', 'hexydec\\html\\text'])) {
				$value = $item->text();
				$text = array_merge($text, is_array($value) ? $value : [$value]);
			}
		}
		return implode(' ', $text);
	}

	/**
	 * Adds the specified nodes to the htmldoc object
	 *
	 * @param array $nodes An array of nodes to add to the collection
	 * @return void
	 */
	protected function collection(array $nodes) : void {
		$this->children = $nodes;
	}

	/**
	 * Minifies the internal representation of the document
	 *
	 * @param array $minify An array indicating which minification operations to perform, this is merged with htmldoc::$config['minify']
	 * @return void
	 */
	public function minify(array $minify = []) : void {

		// merge config
		$minify = array_replace_recursive($this->config['minify'], $minify);

		// set minify output parameters
		if ($minify['quotes']) {
			$this->output['quotestyle'] = 'minimal';
		}

		// email minification
		if ($minify['email']) {
			if ($minify['comments'] !== false) {
				$minify['comments']['ie'] = true;
			}
			$minify['urls'] = false;
			if ($minify['attributes'] !== false) {
				$minify['attributes']['empty'] = false;
			}
			$minify['close'] = false;
		}

		// sort classes by occurence, then by string
		if (is_array($minify['attributes'])) {
			if ($minify['attributes']['class'] && !empty($this->cache['class'])) {
				$minify['attributes']['class'] = array_keys($this->cache['class']);
				$occurences = array_values($this->cache['class']);
				array_multisort($occurences, SORT_DESC, SORT_NUMERIC, $minify['attributes']['class'], SORT_STRING);
			}

			// sort attribute values by most frequent
			if ($minify['attributes']['sort'] && !empty($this->cache['attr'])) {
				arsort($this->cache['attr']);
				arsort($this->cache['attrvalues']);
				$attr = [];
				foreach ($this->cache['attrvalues'] AS $item => $occurences) {
					if ($occurences > 5) {
						$item = mb_strstr($item, '=', true);
						if (!in_array($item, $attr)) {
							$attr[] = $item;
						}
					} else {
						break;
					}
				}
				$minify['attributes']['sort'] = array_unique(array_merge($attr, array_keys($this->cache['attr'])));
			}
		}

		// minify children
		foreach ($this->children AS $item) {
			$item->minify($minify);
		}
	}

	/**
	 * Compile the document as an HTML string
	 *
	 * @param array $options An array indicating output options, this is merged with htmldoc::$output
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		$options = $options ? array_merge($this->output, $options) : $this->output;

		// presets
		if (!empty($options['xml'])) {
			$options['quotestyle'] = 'double';
			$options['singletonclose'] = '/>';
			$options['closetags'] = true;
		}

		// output HTML
		$html = '';
		foreach ($this->children AS $item) {
			$html .= $item->html($options);
		}
		return $html;
	}

	/**
	 * Compile the document as an HTML string and save it to the specified location
	 *
	 * @param array $options An array indicating output options, this is merged with htmldoc::$output
	 * @return string The compiled HTML
	 */
	public function save(string $file = null, array $options = []) {

		// compile html
		$html = $this->html($options);

		// convert charset
		if (!empty($options['charset'])) {

			// if not UTF-8, convert all applicable HTML entities
			if ($options['charset'] != 'UTF-8') {
				$html = $this->htmlentities($html, $options['charset']);
			}

			// convert to target charset
			$html = mb_convert_encoding($html, $options['charset']);
		}

		// send back as string
		if (!$file) {
			return $html;

		// save file
		} elseif (file_put_contents($file, $html) === false) {
			trigger_error('File could not be written', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}

	/**
	 * Converts out of range characters into HTML entities within the selected charset
	 *
	 * @param string $html A UTF-8 encoded HTML string
	 * @param string $charset The target charset
	 * @return string The input HTML with the out of range characters in the selected charset converted to HTML entities
	 */
	protected function htmlentities(string $html, string $charset) : string {

		// generate single-byte characters
		$str = '';
		for ($i = 1; $i < 256; $i++) {
			$str .= chr($i);
		}
		$str = mb_convert_encoding($str, mb_internal_encoding(), $charset);

		// build html entities conversion map
		$replace = [];
		foreach (preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY) AS $chr) {
			$ent = mb_convert_encoding($chr, 'HTML-ENTITIES');
			if ($ent != $chr) {
				$replace[$chr] = $ent;
			}
		}

		// convert entities
		$html = mb_convert_encoding($html, 'HTML-ENTITIES');
		return str_replace(array_values($replace), array_keys($replace), $html);
	}
}
