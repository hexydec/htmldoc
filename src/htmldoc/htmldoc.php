<?php
namespace hexydec\html;

class htmldoc {

	/**
	 * @var Array $token Regexp components keyed by their corresponding codename for tokenising HTML
	 */
	protected static $tokens = Array(
		'doctype' => '<!(?i:DOCTYPE)',
		'comment' => '<!--[\d\D]*?(?<=--)>',
		'cdata' => '<!\[CDATA\[[\d\D]*?\]\]>',
		'tagopenstart' => '<[a-zA-Z][a-zA-Z0-9_:.-]*+',
		'tagselfclose' => '\s*+\/>',
		'tagopenend' => '\s*+>',
		'tagclose' => '<\/[a-zA-Z][a-zA-Z0-9_:.-]*+\s*+>',
		'textnode' => '(?<=>|^)[^<]++(?=<|$)',
		'attributevalue' => '=\s*+(?:"[^"]*+"|\'[^\']*+\'|[^\s>]*+)',
		'attribute' => '[^<>"\'=\s]++',
		'whitespace' => '\s++',
		'other' => '.'
	);

	/**
	 * @var Array $selectors Regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected static $selectors = Array(
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
	);

	/**
	 * @var Array $config Object configuration array
	 */
	protected $config = Array(
		'elements' => Array(
			'inline' => Array(
				'b', 'u', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var', 'span'
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
			'empty' => Array('id', 'class', 'style', 'title', 'action', 'value', 'alt', 'lang', 'dir', 'onfocus', 'onblur', 'onchange', 'onclick', 'ondblclick', 'onmousedown', 'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout', 'onkeypress', 'onkeydown', 'onkeyup'), // attributes to remove if empty
			'urls' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
		),
		'css' => 'hexydec\\html\\cssmin::minify', // specify the CSS minifier
		'js' => false, // specify the javascript minifier
		'minify' => Array(
			'css' => Array(), // specify CSS minifier options
			'js' => Array(), // specify CSS javascript options
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => Array( // remove comments
				'ie' => true
			),
			'urls' => Array( // update internal URL's to be shorter
				'scheme' => true, // remove the scheme from URLs that have the same scheme as the current document
				'host' => true, // remove the host for own domain
				'relative' => true, // process absolute URLs to make them relative to the current document
				'parent' => true // process relative URLs to use relative parent links where it is shorter
			),
			'attributes' => Array( // minify attributes
				'default' => true, // remove default attributes
				'empty' => true, // remove these attributes if empty
				'option' => true, // remove value attribute from option where the text node has the same value
				'style' => true, // minify the style tag
				'class' => true, // sort classes
				'sort' => true, // sort attributes for better gzip
				'boolean' => true // minify boolean attributes
			),
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // minify attribute quotes
			'close' => true, // don't write close tags where possible
			'email' => false // sets the minification presets to email safe options
		)
	);

	/**
	 * @var Array Contains the output settings
	 */
	protected $output = Array(
		'charset' => null, // set the output charset
		'quotestyle' => 'double', // double, single, minimal
		'singletonclose' => false, // string to close singleton tags, or false to leave as is
		'closetags' => false, // whether to force tags to have a closing tag (true) or follow tag::close
		'xml' => false // sets the output presets to produce XML valid code
	);

	/**
	 * @var Array $children Stores the regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected $children = Array();

	/**
	 * @var Array A cache of attribute and class names for sorting
	 */
	protected $cache = Array();

	/**
	 * Constructs the object
	 *
	 * @param Array $config An array of configuration parameters that is recursively merged with the default config
	 */
	public function __construct(array $config = Array()) {
		$this->config = array_replace_recursive($this->config, $config);
	}

	/**
	 * Calculates the length property
	 *
	 * @param string $var The name of the property to retrieve, currently only 'length'
	 * @return int The number of children in the object, or null if the parameter doesn't exist
	 */
	public function __get(string $var) : ?int {
		if ($var == 'length') {
			return count($this->children);
		}
		return null;
	}

	/**
	 * Retrieves the children of the document as an array
	 *
	 * @return Array An array of child nodes
	 */
	public function toArray() {
		return $this->children;
	}

	/**
	 * Retrieves the requested value of the object configuration
	 *
	 * @param string $key... One or more array keys indicating the configuration value to retrieve
	 * @return mixed The value requested, or null if the value doesn't exist
	 */
	public function getConfig(...$keys) {
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
	 * @param string $context An optional array of context parameters
	 * @return mixed The loaded HTML, or false on error
	 */
	public function open(String $url, Resource $context = null, String &$error = null) {

		// open a handle to the stream
		if (($handle = @fopen($url, 'rb', $context)) === false) {
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
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $html, string $charset = null, &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== false || ($charset = mb_detect_encoding($html)) !== false) {
			$html = mb_convert_encoding($html, mb_internal_encoding(), $charset);
		}

		// reset the document
		$this->children = Array();

		// tokenise the input HTML
		if (($tokens = tokenise::tokenise($html, self::$tokens)) === false) {
			$error = 'Could not tokenise input';

		// parse the document
		} elseif (!$this->parse($tokens)) {
			$error = 'Input is not invalid';

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
	 * @return string The defined or detected charset or false if the charset is not defined
	 */
	protected function getCharsetFromHtml(string $html) {
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
		}
		return false;
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param array &$tokens An array of tokens generated by tokenise()
	 * @return bool Whether the parser was able to capture any objects
	 */
	protected function parse(array &$tokens) {
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
	protected static function parseSelector(string $selector) {
		$selector = trim($selector);
		if (($tokens = tokenise::tokenise($selector, self::$selectors)) !== false) {
			$selectors = $parts = Array();
			$join = false;
			$token = current($tokens);
			do {
				switch ($token['type']) {
					case 'id':
						$parts[] = Array(
							'id' => mb_substr($token['value'], 1),
							'join' => $join
						);
						$join = false;
						break;
					case 'class':
						$parts[] = Array(
							'class' => mb_substr($token['value'], 1),
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
					case 'pseudo':
						$parts[] = Array(
							'pseudo' => mb_substr($token['value'], 1),
							'join' => $join
						);
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
					case 'comma':
						$selectors[] = $parts;
						$parts = Array();
						break;
				}
			} while (($token = next($tokens)) !== false);
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
			$this->cache[$key] = Array();
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
		$children = Array();
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
			$doc->collection(Array($this->children[$index]));
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
		$text = Array();
		foreach ($this->children AS $item) {

			// only get text from these objects
			if (in_array(get_class($item), Array('hexydec\\html\\tag', 'hexydec\\html\\text'))) {
				$value = $item->text();
				$text = array_merge($text, is_array($value) ? $value : Array($value));
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
	public function minify(array $minify = Array()) : void {

		// merge config
		$minify = array_replace_recursive($this->config['minify'], $minify);

		// set minify output parameters
		if ($minify['quotes']) {
			$this->output['quotestyle'] = 'minimal';
		}

		// email minification
		if (!empty($options['email'])) {
			if ($minify['comments'] !== false) {
				$minify['comments']['ie'] = true;
			}
			$minify['url'] = false;
			if ($minify['attributes'] !== false) {
				$minify['attributes']['empty'] = false;
			}
			$minify['close'] = false;
		}

		// sort classes by occurence, then by string
		if ($minify['attributes']['class'] && !empty($this->cache['class'])) {
			$minify['attributes']['class'] = array_keys($this->cache['class']);
			$occurences = array_values($this->cache['class']);
			array_multisort($occurences, SORT_DESC, SORT_NUMERIC, $minify['attributes']['class'], SORT_STRING);
		}

		// sort attribute values by most frequent
		if ($minify['attributes']['sort'] && !empty($this->cache['attr'])) {
			arsort($this->cache['attr']);
			arsort($this->cache['attrvalues']);
			$attr = Array();
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
	public function html(array $options = null) : string {
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
	public function save(string $file = null, Array $options = Array()) {

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
	 * Converts out of tange characters into HTML entities within the selected charset
	 *
	 * @param string $html A UTF-8 encoded HTML string
	 * @param string $charset The target charset
	 * @return string The input HTML with the out of range characters in the selected cahrset converted to HTML entities
	 */
	protected function htmlentities(string $html, string $charset) {

		// generate single-byte characters
		$str = '';
		for ($i = 1; $i < 256; $i++) {
			$str .= chr($i);
		}
		$str = mb_convert_encoding($str, mb_internal_encoding(), $charset);

		// build html entities conversion map
		$replace = Array();
		foreach (preg_split('//u', $str, null, PREG_SPLIT_NO_EMPTY) AS $chr) {
			$ent = mb_convert_encoding($chr, 'HTML-ENTITIES');
			if ($ent != $chr) {
				$replace[$chr] = $ent;
			}
		}

		// convert entities
		$html = mb_convert_encoding($html, 'HTML-ENTITIES');
		return str_replace(array_values($replace), array_keys($replace), $html);
	}

	// public function debug() {
	// 	$output = Array();
	// 	foreach ($this->children AS $item) {
	// 		$node = Array(
	// 			'type' => get_class($item)
	// 		);
	// 		switch ($node['type']) {
	// 			case 'hexydec\\html\\tag':
	// 				$node['tag'] = $item->tagName;
	// 				$node['attributes'] = $item->attributes;
	// 				$node['singleton'] = $item->singleton;
	// 				$node['close'] = $item->close;
	// 				if ($item->children) {
	// 					$node['children'] = $item->children->debug();
	// 				}
	// 				break;
	// 			case 'hexydec\\html\\doctype':
	// 				$node['doctype'] = $item->content;
	// 				break;
	// 			default:
	// 				$node['content'] = $item->content;
	// 				break;
	// 		}
	// 		$output[] = $node;
	// 	}
	// 	return $output;
	// }
}
