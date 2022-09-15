<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

/**
 * @property array $config
 * @property-read int $length
 */
class htmldoc extends config implements \ArrayAccess, \Iterator {

	/**
	 * @var array $tokens Regexp components keyed by their corresponding codename for tokenising HTML
	 */
	protected static array $tokens = [
		'textnode' => '(?<=>|^)[^<]++(?=<|$)',
		'attributevalue' => '\\s*+=\\s*+(?:"[^"]*+"++|\'[^\']*+\'++|[^\\s>]*+)',
		'attribute' => '\\s*+[^<>"\'\\/=\\s]++',
		'tagopenend' => '\\s*+>',
		'tagselfclose' => '\\s*+\\/>',
		'tagopenstart' => '<[a-zA-Z][a-zA-Z0-9_:.-]*+',
		'tagclose' => '<\\/[a-zA-Z][a-zA-Z0-9_:.-]*+\\s*+>',
		'doctype' => '<!(?i:DOCTYPE)',
		'comment' => '<!--[\\d\\D]*?(?<=--)>',
		'cdata' => '<!\\[CDATA\\[[\\d\\D]*?\\]\\]>',
		'quotes' => '\\s*+(?:"[^"]*+"|\'[^\']*+\')',
		'other' => '.'
	];

	/**
	 * @var array $selectors Regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected static array $selectors = [
		'quotes' => '(?<!\\\\)"(?:[^"\\\\]++|\\\\.)*+"',
		'join' => '\\s*[>+~]\\s*',
		'comparison' => '[\\^*$<>]?=', // comparison operators for media queries or attribute selectors
		'squareopen' => '\\[',
		'squareclose' => '\\]',
		'bracketopen' => '\\(',
		'bracketclose' => '\\)',
		'comma' => ',',
		'pseudo' => ':[A-Za-z-]++',
		'id' => '#[^ +>\.#{\\[,]++',
		'class' => '\.[^ +>\.#{\\[\\(\\),]++',
		'string' => '\\*|[^\\[\\]{}\\(\\):;,>+=~\\^$!" #\\.*]++',
		'whitespace' => '\s++',
	];

	/**
	 * @var array $children Stores the regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected array $children = [];

	/**
	 * @var int $pointer The current pointer position for the array iterator
	 */
	protected int $pointer = 0;

	/**
	 * @var array A cache of attribute and class names for sorting
	 */
	protected array $cache = [];

	/**
	 * Calculates the length property
	 *
	 * @param string $var The name of the property to retrieve, currently 'length' and output
	 * @return mixed The number of children in the object for length, the output config, or null if the parameter doesn't exist
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $var) {
		if ($var === 'config') {
			return $this->config;
		} elseif ($var === 'length') {
			return \count($this->children);
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
	 * @param mixed $i The key to be updated, can be a string or integer
	 * @param mixed $value The value of the array key in the children array to be updated
	 */
	public function offsetSet($i, $value) : void {
		$this->children[$i] = $value;
	}

	/**
	 * Array access method allows you to check that a key exists in the configuration array
	 *
	 * @param mixed $i The key to be checked
	 * @return bool Whether the key exists in the config array
	 */
	public function offsetExists($i) : bool {
		return isset($this->children[$i]);
	}

	/**
	 * Removes a key from the configuration array
	 *
	 * @param mixed $i The key to be removed
	 */
	public function offsetUnset($i) : void {
		unset($this->children[$i]);
	}

	/**
	 * Retrieves a value from the configuration array with the specified key
	 *
	 * @param mixed $i The key to be accessed, can be a string or integer
	 * @return mixed An HTMLdoc object containing the child node at the requested position or null if there is no child at the requested position
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($i) { // return reference so you can set it like an array
		if (isset($this->children[$i])) {
			$obj = new htmldoc($this->config);
			$obj->collection([$this->children[$i]]);
			return $obj;
		}
		return null;
	}

	/**
	 * Retrieve the document node in the current position
	 *
	 * @return mixed An HTMLdoc object containing the child node at the current pointer position or null if there are no children
	 */
	#[\ReturnTypeWillChange]
	public function current() {
		if (isset($this->children[$this->pointer])) {
			$obj = new htmldoc($this->config);
			$obj->collection([$this->children[$this->pointer]]);
			return $obj;
		}
		return null;
	}

	/**
	 * Retrieve the the current pointer position for the object
	 *
	 * @return mixed The current pointer position
	 */
	public function key() {
		return $this->pointer;
	}

	/**
	 * Increments the pointer position
	 *
	 * @return void
	 */
	public function next() : void {
		$this->pointer++;
	}

	/**
	 * Decrements the pointer position
	 *
	 * @return void
	 */
	public function rewind() : void {
		$this->pointer = 0;
	}

	/**
	 * Determines whether there is a node at the current pointer position
	 *
	 * @return bool Whether there is a node at the current pointer position
	 */
	public function valid() : bool {
		return isset($this->children[$this->pointer]);
	}

	/**
	 * Open an HTML file from a URL
	 *
	 * @param string $url The address of the HTML file to retrieve
	 * @param resource $context A resource object made with stream_context_create()
	 * @param ?string &$error A reference to any user error that is generated
	 * @return string|false The loaded HTML, or false on error
	 */
	public function open(string $url, $context = null, ?string &$error = null) {

		// check resource
		if ($context !== null && !\is_resource($context)) {
			$error = 'The supplied context is not a valid resource';

		// open a handle to the stream
		} elseif (($handle = @\fopen($url, 'rb', false, $context)) === false) {
			$error = 'Could not open file "'.$url.'"';

		// retrieve the stream contents
		} elseif (($html = \stream_get_contents($handle)) === false) {
			$error = 'Could not read file "'.$url.'"';

		// success
		} else {

			// find charset in headers
			$charset = null;
			$meta = \stream_get_meta_data($handle);
			foreach ($meta['wrapper_data'] ?? [] AS $item) {
				if (\mb_stripos($item, 'Content-Type:') === 0 && ($value = \mb_stristr($item, 'charset=')) !== false) {
					$charset = \mb_substr($value, 8);
					break;
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
	 * @param ?string &$error A reference to any user error that is generated
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $html, string $charset = null, ?string &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== null) {
			$html = \mb_convert_encoding($html, \mb_internal_encoding(), $charset);
		}

		// reset the document
		$this->children = [];

		// parse the document
		if (($nodes = $this->parse($html)) === false) {
			$error = 'Input is not valid';

		// success
		} else {
			$this->children = $nodes;
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
		$obj = new htmldoc($this->config);
		$pat = '/<meta[^>]+charset[^>]+>/i';
		if (\preg_match($pat, $html, $match) && $obj->load($match[0], \mb_internal_encoding())) {

			// <meta charset="xxx" />
			if (($charset = $obj->attr('charset')) !== null && $this->isEncodingValid($charset)) {
				return $charset;

			// <meta http-equiv="Content-Type" content="text/html; charset=xxx" />
			} elseif (($value = $obj->eq(0)->attr('content')) !== null && ($charset = \mb_stristr($value, 'charset=')) !== false) {
				$charset = \mb_substr($charset, 8);
				if ($this->isEncodingValid($charset)) {
					return $charset;
				}
			}
		}

		// just detect the charset
		if (($charset = \mb_detect_encoding($html)) !== false) {
			return $charset;
		}
		return null;
	}

	protected function isEncodingValid(string $charset) : bool {
		return \in_array(\strtolower($charset), \array_map('\\strtolower', \mb_list_encodings()), true);
	}

	/**
	 * Parses an array of tokens into an HTML document
	 *
	 * @param string|htmldoc $html A string of HTML, or an htmldoc object
	 * @return bool|array An array of node objects or false on error
	 */
	protected function parse($html) {

		// convert string to nodes
		if (\is_string($html)) {

			// tokenise the input HTML
			$tokens = new tokenise(self::$tokens, $html);
			// while (($token = $tokens->next()) !== null) {
			// 	var_dump($token);
			// }
			// exit();
			$tag = new tag($this);
			return $tag->parseChildren($tokens);

		// extract nodes from HTMLdoc
		} elseif (\get_class($html) === 'hexydec\\html\\htmldoc') {
			return $html->toArray();
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
			if (\get_class($item) === 'hexydec\\html\\tag') {
				$children[] = $item;
			}
		}

		// return all children if no index
		if ($index === null) {
			return $children;
		}

		// check if index is minus
		if ($index < 0) {
			$index = \count($children) + $index;
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
		$obj = new selector();

		// parse selector and find tags
		$found = [];
		if (($tokens = $obj->get($selector)) !== false) {
			foreach ($this->children AS $item) {
				if (\get_class($item) === 'hexydec\\html\\tag') {
					foreach ($tokens AS $value) {
						if (($items = $item->find($value)) !== false) {
							$found = \array_merge($found, $items);
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
			$index = \count($this->children) + $index;
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
	 * Retrieves the specified attribute value from the first tag in the collection or update the attribute on all matching tags
	 *
	 * @param string $key The name of the attribute to retrieve
	 * @param string $value The value of the attribute to update
	 * @return string The value of the attribute or null if the attribute doesn't exist
	 */
	public function attr(string $key, ?string $value = null) : ?string {
		foreach ($this->children AS $item) {
			if (\get_class($item) === 'hexydec\\html\\tag') {
				if ($value === null) {
					return $item->attr($key);
				} else {
					$item->attr($key, $value);
				}
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
			if (\in_array(\get_class($item), ['hexydec\\html\\tag', 'hexydec\\html\\text'], true)) {
				$text[] = $item->text();
			}
		}
		return \implode(' ', $text);
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
		$minify = \array_replace_recursive($this->config['minify'], $minify);

		// set minify output parameters
		if ($minify['quotes']) {
			$this->config['output']['quotestyle'] = 'minimal';
		}

		// set safe options
		if ($minify['safe']) {
			$minify['urls'] = false;
			if ($minify['attributes'] !== false) {
				$minify['attributes']['empty'] = false;
				$minify['attributes']['default'] = false;
			}
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
		if (!empty($minify['attributes']['sort']) && !empty($this->cache['attr'])) {
			$minify['attributes']['sort'] = $this->sortAttributes($this->cache['attr'], $this->cache['attrvalues']);
		}

		// minify children
		foreach ($this->children AS $item) {
			$item->minify($minify);
		}
	}

	/**
	 * Sort attributes in frequency order
	 *
	 * @param array $attr An array of attribute keys
	 * @param array $values An array of attribute values
	 * @return array An array of attributes ordered by frequency
	 */
	protected function sortAttributes(array $attr, array $values) : array {
		\arsort($attr, SORT_NUMERIC);
		\arsort($values, SORT_NUMERIC);
		$items = [];
		foreach ($values AS $item => $occurences) {
			if ($occurences > 5) {
				$item = \mb_strstr($item, '=', true);
				if (!\in_array($item, $items, true)) {
					$items[] = $item;
				}
			} else {
				break;
			}
		}
		return \array_unique(\array_merge($items, \array_keys($attr)));
	}

	/**
	 * Compile the document as an HTML string
	 *
	 * @param array $options An array indicating output options, this is merged with htmldoc::$output
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		$options = $options ? \array_merge($this->config['output'], $options) : $this->config['output'];

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
	 * Append HTML or another document to each node in the current document
	 *
	 * @param string|htmldoc $html A string of HTML, or an htmldoc object
	 * @return htmldoc The current htmldoc object with the nodes appended
	 */
	public function append($html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->children AS $item) {
				if (\get_class($item) === 'hexydec\\html\\tag') {
					$item->append($nodes);
				}
			}
		}
		return $this;
	}

	/**
	 * Prepend HTML or another document to each node in the current document
	 *
	 * @param string|htmldoc $html A string of HTML, or an htmldoc object
	 * @return htmldoc The current htmldoc object with the nodes appended
	 */
	public function prepend($html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->children AS $item) {
				if (\get_class($item) === 'hexydec\\html\\tag') {
					$item->prepend($nodes);
				}
			}
		}
		return $this;
	}

	/**
	 * Insert an array of nodes before the each node in the current document
	 *
	 * @param string|htmldoc $html A string of HTML, or an htmldoc object
	 * @return htmldoc The current htmldoc object with the nodes appended
	 */
	public function before($html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->children AS $item) {
				if (\get_class($item) === 'hexydec\\html\\tag') {
					$item->before($nodes);
				}
			}
		}
		return $this;
	}

	/**
	 * Insert an array of nodes after the each node in the current document
	 *
	 * @param string|htmldoc $html A string of HTML, or an htmldoc object
	 * @return htmldoc The current htmldoc object with the nodes appended
	 */
	public function after($html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->children AS $item) {
				if (\get_class($item) === 'hexydec\\html\\tag') {
					$item->after($nodes);
				}
			}
		}
		return $this;
	}

	/**
	 * Removes all top level nodes, or if $selector is specified, the nodes matched by the selector
	 *
	 * @param string $selector A CSS selector to refine the nodes to delete or null to delete top level nodes
	 * @return htmldoc The current htmldoc object with the requested nodes deleted
	 */
	public function remove(string $selector = null) : htmldoc {
		$obj = $selector ? $this->find($selector) : $this;
		foreach ($obj->children AS $item) {
			if (\get_class($item) === 'hexydec\\html\\tag') {
				$item->parent()->remove($item);
			}
		}
		return $this;
	}

	/**
	 * Compile the document as an HTML string and save it to the specified location
	 *
	 * @param string|null $file The file location to save the document to, or null to just return the compiled code
	 * @param array $options An array indicating output options, this is merged with htmldoc::$output
	 * @return string|bool The compiled HTML, or false if the file could not be saved
	 */
	public function save(string $file = null, array $options = []) {

		// compile html
		$html = $this->html($options);

		// convert charset
		if (!empty($options['charset'])) {

			// if not UTF-8, convert all applicable HTML entities
			if ($options['charset'] !== 'UTF-8') {
				$html = $this->htmlentities($html, $options['charset']);
			}

			// convert to target charset
			$html = (string) \mb_convert_encoding($html, $options['charset']);
		}

		// save file
		if ($file && \file_put_contents($file, $html) === false) {
			\trigger_error('File could not be written', E_USER_WARNING);
			return false;
		}
		return $html;
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
			$str .= \chr($i);
		}
		$str = (string) \mb_convert_encoding($str, \mb_internal_encoding(), $charset);

		// build html entities conversion map
		$replace = [];
		foreach (\preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY) AS $chr) {
			$ent = \mb_convert_encoding($chr, 'HTML-ENTITIES');
			if ($ent !== $chr) {
				$replace[$chr] = $ent;
			}
		}

		// convert entities
		$html = (string) \mb_convert_encoding($html, 'HTML-ENTITIES');
		return \str_replace(\array_values($replace), \array_keys($replace), $html);
	}
}
