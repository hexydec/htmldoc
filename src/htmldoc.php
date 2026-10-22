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
		'textnode' => '(?<=>|^)(?:[^<]++|<(?![a-zA-Z\\/!?]))++', // a < that cannot start markup is text
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
	 * @var array $children The nodes of a collection, a loaded document holds its nodes in $document instead
	 */
	protected array $children = [];

	/**
	 * @var ?tag $document The root tag of a loaded document, which holds its top level nodes
	 */
	protected ?tag $document = null;

	/**
	 * @var array $lookup Lowercase tag name lists built from the config, keyed by list name
	 */
	protected array $lookup = [];

	/**
	 * @var ?array $currentUrl The parsed URL of the current page for minifying URLs, an empty array when it is not available, or null when not yet parsed
	 */
	protected ?array $currentUrl = null;

	/**
	 * @var int $pointer The current pointer position for the array iterator
	 */
	protected int $pointer = 0;

	/**
	 * @var array A cache of attribute and class names for sorting
	 */
	protected array $cache = [];

	/**
	 * @var array $minified Output options set by the last minification, which take precedence over the output config but not the options passed to html()
	 */
	protected array $minified = [];

	/**
	 * Calculates the length property
	 *
	 * @param string $var The name of the property to retrieve, 'config', 'length', or a property of the first tag
	 * @return mixed The number of children in the object for length, the config, the property of the first tag, or null if there are no tags
	 */
	public function __get(string $var) : mixed {
		if ($var === 'config') {
			return $this->config;
		} elseif ($var === 'length') {
			return \count($this->toArray());
		} else {
			return $this->get(0)?->{$var};
		}
	}

	/**
	 * Retrieves the children of the document as an array
	 *
	 * @return array An array of child nodes
	 */
	public function toArray() : array {
		return $this->document === null ? $this->children : $this->document->toArray();
	}

	/**
	 * Array access method allows you to set a node at the specified position, or append it when no position is given
	 *
	 * @param mixed $i The integer position to set, or null to append
	 * @param mixed $value The node object to set, which must implement \hexydec\html\token
	 * @throws \TypeError When the position is not an integer or null, or the value is not a node
	 */
	public function offsetSet(mixed $i, mixed $value) : void {
		if ($i !== null && !\is_int($i)) {
			throw new \TypeError('The position must be an integer, or null to append');
		} elseif (!$value instanceof token) {
			throw new \TypeError('The value must be a node that implements \\hexydec\\html\\token');
		} else {

			// nodes set on a document belong to its root
			if ($this->document !== null) {
				$value->parent = $this->document;
			}
			$children = $this->toArray();
			if ($i === null) {
				$children[] = $value;
			} else {
				$children[$i] = $value;
			}
			$this->setNodes($children);
		}
	}

	/**
	 * Array access method allows you to check that a node exists at the specified position
	 *
	 * @param mixed $i The position to be checked
	 * @return bool Whether a node exists at the position
	 */
	public function offsetExists(mixed $i) : bool {
		$children = $this->toArray();
		return isset($children[$i]);
	}

	/**
	 * Array access method allows you to remove the node at the specified position
	 *
	 * @param mixed $i The position to be removed
	 */
	public function offsetUnset(mixed $i) : void {
		$children = $this->toArray();
		unset($children[$i]);
		$this->setNodes($children);
	}

	/**
	 * Replaces the nodes of the collection, or of the root of a loaded document
	 *
	 * @param array $nodes An array of node objects
	 * @return void
	 */
	protected function setNodes(array $nodes) : void {
		if ($this->document === null) {
			$this->children = $nodes;
		} else {
			$this->document->children = $nodes;
		}
	}

	/**
	 * Retrieves an htmldoc object containing the node at the specified position
	 *
	 * @param mixed $i The key to be accessed, can be a string or integer
	 * @return mixed An HTMLdoc object containing the child node at the requested position or null if there is no child at the requested position
	 */
	public function offsetGet(mixed $i) : mixed { // return reference so you can set it like an array
		$children = $this->toArray();
		if (isset($children[$i])) {
			return $this->collection([$children[$i]]);
		}
		return null;
	}

	/**
	 * Retrieve the document node in the current position
	 *
	 * @return mixed An HTMLdoc object containing the child node at the current pointer position or null if there are no children
	 */
	public function current() : mixed {
		$children = $this->toArray();
		if (isset($children[$this->pointer])) {
			return $this->collection([$children[$this->pointer]]);
		}
		return null;
	}

	/**
	 * Retrieve the the current pointer position for the object
	 *
	 * @return mixed The current pointer position
	 */
	public function key() : mixed {
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
	 * Resets the pointer position to the start
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
		$children = $this->toArray();
		return isset($children[$this->pointer]);
	}

	/**
	 * Open an HTML file from a URL
	 *
	 * @param string $url The address of the HTML file to retrieve
	 * @param resource $context A resource object made with stream_context_create()
	 * @param ?string &$error A reference to any user error that is generated
	 * @return string|false The loaded HTML, or false on error
	 */
	public function open(string $url, $context = null, ?string &$error = null) : string|false {

		// check resource
		if ($context !== null && !\is_resource($context)) {
			$error = 'The supplied context is not a valid resource';

		// open a handle to the stream
		} elseif (($handle = @\fopen($url, 'rb', false, $context)) === false) {
			$error = 'Could not open file "'.$url.'"';

		// retrieve the stream contents and headers, then close the stream
		} else {
			$html = \stream_get_contents($handle);
			$meta = \stream_get_meta_data($handle);
			\fclose($handle);

			// check the contents were read
			if ($html === false) {
				$error = 'Could not read file "'.$url.'"';

			// load the html using the charset of the final response, if it is valid
			} else {
				$charset = $this->getHeaderValue($meta['wrapper_data'] ?? [], 'Content-Type', 'charset');
				if ($this->load($html, $charset !== null && $this->isEncodingValid($charset) ? $charset : null, $error)) {
					return $html;
				}
			}
		}
		return false;
	}

	/**
	 * Retrieves the value of a header from the last response in a list of headers, such as the wrapper data of an HTTP stream
	 *
	 * @param array $headers A list of header lines, where each response starts with its HTTP status line
	 * @param string $name The name of the header to retrieve
	 * @param ?string $parameter The name of a parameter to retrieve from the header value, such as charset, or null for the whole value
	 * @return ?string The header or parameter value, or null if it is not present in the last response
	 */
	protected function getHeaderValue(array $headers, string $name, ?string $parameter = null) : ?string {

		// find the header, resetting at each status line so only the last response is used
		$value = null;
		$len = \strlen($name) + 1;
		foreach ($headers AS $item) {
			if (\strncasecmp($item, 'HTTP/', 5) === 0) {
				$value = null;
			} elseif (\strncasecmp($item, $name.':', $len) === 0) {
				$value = \trim(\substr($item, $len));
			}
		}

		// extract the parameter from the header value, unquoting it
		if ($parameter !== null && $value !== null) {
			$found = null;
			$len = \strlen($parameter) + 1;
			foreach (\explode(';', $value) AS $item) {
				$item = \trim($item);
				if (\strncasecmp($item, $parameter.'=', $len) === 0) {
					$found = \trim(\substr($item, $len), " \t\"");
					break;
				}
			}
			$value = $found;
		}
		return $value;
	}

	/**
	 * Parse an HTML string into the object
	 *
	 * @param string $html A string containing valid HTML
	 * @param string $charset The charset of the document
	 * @param ?string &$error A reference to any user error that is generated
	 * @return bool Whether the input HTML was parsed
	 */
	public function load(string $html, ?string $charset = null, ?string &$error = null) : bool {

		// detect the charset
		if ($charset || ($charset = $this->getCharsetFromHtml($html)) !== null) {
			$html = \mb_convert_encoding($html, \mb_internal_encoding(), $charset);
		}

		// parse the document, replacing any collection and the attribute counts of any previous document
		$this->children = [];
		$this->cache = [];
		$this->document = $this->parseDocument($html);
		return true;
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
	 * @return array|false An array of node objects, or false if there are no nodes, such as from an empty string
	 */
	protected function parse(string|htmldoc $html) : array|false {

		// convert a string to nodes, or extract the nodes from an htmldoc object
		$nodes = \is_string($html) ? $this->parseDocument($html)->toArray() : $html->toArray();
		return $nodes ?: false;
	}

	/**
	 * Parses an HTML string into a root tag object that holds the top level nodes
	 *
	 * @param string $html A string of HTML
	 * @return tag A tag object with no tag name, whose children are the parsed nodes
	 */
	protected function parseDocument(string $html) : tag {
		$tokens = new tokenise(self::$tokens, $html);
		$tag = new tag($this);
		$tag->children = $tag->parseChildren($tokens);
		return $tag;
	}

	/**
	 * Retrieves the value stored against a tag name in a lookup list, building the list on first use
	 *
	 * @param string $list The list to search: singleton, closeoptional, inline, inlineblock or custom
	 * @param string $name The lowercase tag name to look up
	 * @return mixed The stored value, or null if the name is not in the list
	 */
	public function lookup(string $list, string $name) : mixed {
		if (!isset($this->lookup[$list])) {

			// custom handlers store their config
			if ($list === 'custom') {
				$this->lookup[$list] = \array_change_key_case($this->config['custom']);

			// element lists store true
			} elseif (\array_is_list($items = $this->config['elements'][$list])) {
				$this->lookup[$list] = \array_fill_keys(\array_map('\\strtolower', $items), true);

			// element maps store their lowercase list of tag names
			} else {
				$this->lookup[$list] = [];
				foreach ($items AS $key => $item) {
					$this->lookup[$list][\strtolower($key)] = \array_map('\\strtolower', $item);
				}
			}
		}
		return $this->lookup[$list][$name] ?? null;
	}

	/**
	 * Retrieves the parsed URL of the current page, parsing it from the request on first use
	 *
	 * @return ?array The scheme, host, path, query, request URI, folder, and folder segments of the page, or null if the URL is not available or a <base> tag changes how URLs resolve
	 */
	public function getCurrentUrl() : ?array {
		if ($this->currentUrl === null) {
			$this->currentUrl = [];
			$uri = $_SERVER['REQUEST_URI'] ?? '';

			// parse the request, as long as there is no base URL to resolve against instead
			if (isset($_SERVER['HTTP_HOST']) && \str_starts_with($uri, '/') && !$this->find('base[href]')->length) {
				$path = \substr($uri, 0, \strcspn($uri, '?#'));
				$folder = \substr($path, 0, \strrpos($path, '/') + 1);
				$this->currentUrl = [
					'scheme' => 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '').'://',
					'host' => $_SERVER['HTTP_HOST'],
					'path' => $path,
					'query' => \str_contains($uri, '?') ? \substr(\strstr($uri, '?'), 1) : null,
					'uri' => $uri,
					'folder' => $folder,
					'dirs' => $folder === '/' ? [] : \explode('/', \trim($folder, '/'))
				];
			}
		}
		return $this->currentUrl ?: null;
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
	 * @return tag|array|null A tag object if index is specified, or an array of tag objects, or null if the specified index doesn't exist or the object is empty
	 */
	public function get(?int $index = null) : tag|array|null {

		// build children that are tags
		$children = [];
		foreach ($this->toArray() AS $item) {
			if ($item instanceof tag) {
				$children[] = $item;
			}
		}

		// return all children if no index
		if ($index === null) {
			return $children;
		} else {

			// check if index is minus
			if ($index < 0) {
				$index = \count($children) + $index;
			}

			// return index if set
			if (isset($children[$index])) {
				return $children[$index];
			}
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

		// a document searches from its top level nodes, a collection searches inside its nodes
		$nodes = $this->document === null ? $this->childTags() : $this->toArray();
		return $this->search($selector, $nodes);
	}

	/**
	 * Finds the tags matching a CSS selector within the specified nodes, including the nodes themselves
	 *
	 * @param string $selector A CSS selector specifying the tags to find
	 * @param array $nodes The nodes to search from
	 * @return htmldoc A new htmldoc object containing the found tag items
	 */
	protected function search(string $selector, array $nodes) : htmldoc {
		$obj = new selector();

		// parse selector and find tags in document order
		$found = [];
		if (($groups = $obj->get($selector)) !== false) {
			foreach ($nodes AS $item) {
				if ($item instanceof tag) {
					$item->find($groups, $found);
				}
			}
		}

		return $this->collection($found);
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
		$tag = $this->get($index);
		return $this->collection($tag === null ? [] : [$tag]);
	}

	/**
	 * Generate a new htmldoc object containing the direct child tags of each tag in the collection
	 *
	 * @return htmldoc A new htmldoc object
	 */
	public function children() : htmldoc {
		return $this->collection($this->childTags());
	}

	/**
	 * Retrieves the child tags of each tag in the collection
	 *
	 * @return array An array of tag objects
	 */
	protected function childTags() : array {
		$nodes = [];
		foreach ($this->toArray() AS $item) {
			if ($item instanceof tag) {
				$nodes = \array_merge($nodes, $item->children());
			}
		}
		return $nodes;
	}

	/**
	 * Generate a new htmldoc object containing the parent tag of each tag in the collection
	 *
	 * @return htmldoc A new htmldoc object
	 */
	public function parent() : htmldoc {
		$nodes = [];

		// collect the parent tag of each tag, the document root is not a tag so is skipped
		foreach ($this->toArray() AS $item) {
			if ($item instanceof tag && ($parent = $item->parent) !== null && $parent->tagName !== null) {
				$nodes[] = $parent;
			}
		}
		return $this->collection($nodes);
	}

	/**
	 * Retrieves the tag name from the first tag object in the collection
	 * 
	 * @return ?string The name of the tag, or null if there are no tags in the collection
	 */
	public function tag() : ?string {
		foreach ($this->toArray() AS $item) {
			if ($item instanceof tag) {
				return $item->tagName;
			}
		}
		return null;
	}

	/**
	 * Retrieves the specified attribute value from the first tag in the collection or update the attribute on all matching tags
	 *
	 * @param string $key The name of the attribute to retrieve
	 * @param string $value The value of the attribute to update
	 * @return ?string The value of the attribute or null if the attribute doesn't exist
	 */
	public function attr(string $key, ?string $value = null) : ?string {
		foreach ($this->toArray() AS $item) {
			if ($item instanceof tag) {
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
		foreach ($this->toArray() AS $item) {

			// only get text from these objects
			if ($item instanceof tag || $item instanceof text || $item instanceof cdata) {
				$text[] = $item->text();
			}
		}
		return \implode(' ', $text);
	}

	/**
	 * Creates a new htmldoc collection containing the specified nodes, with the same config and minified output settings as this object
	 *
	 * @param array $nodes An array of nodes to add to the collection
	 * @return htmldoc A new htmldoc object containing the nodes
	 */
	protected function collection(array $nodes) : htmldoc {
		$doc = new htmldoc($this->config);
		$doc->minified = $this->minified;

		// only store unique nodes in their first position, keyed by object so this stays linear
		$unique = [];
		foreach ($nodes AS $item) {
			$unique[\spl_object_id($item)] = $item;
		}
		$doc->children = \array_values($unique);
		return $doc;
	}

	/**
	 * Minifies the internal representation of the document
	 *
	 * @param array $minify An array indicating which minification operations to perform, this is merged with htmldoc::$config['minify']
	 * @return void
	 */
	public function minify(array $minify = []) : void {

		// merge config, and parse the page URL afresh as the request may have changed
		$minify = $this->getMinifyOptions($minify);
		$this->currentUrl = null;

		// set the output options implied by this minification, replacing those of any previous one
		$this->minified = $minify['quotes'] ? ['quotestyle' => 'minimal'] : [];

		// sort classes by occurence, then by string
		if (!empty($minify['attributes']['sort']) && !empty($this->cache['attr'])) {
			$minify['attributes']['sort'] = $this->sortAttributes($this->cache['attr'], $this->cache['attrvalues']);
		}

		// minify children
		foreach ($this->toArray() AS $item) {
			$item->minify($minify);
		}
	}

	protected function getMinifyOptions(array $minify) : array {

		// merge config
		$minify = \array_replace_recursive($this->config['minify'], $minify);

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
		return $minify;
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
		$options = \array_merge($this->config['output'], $this->minified, $options);

		// presets
		if (!empty($options['xml'])) {
			$options['quotestyle'] = 'double';
			$options['singletonclose'] = '/>';
			$options['closetags'] = true;
		}

		// output HTML
		$html = '';
		foreach ($this->toArray() AS $item) {
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
	public function append(string|htmldoc $html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->toArray() AS $item) {
				if ($item instanceof tag) {
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
	public function prepend(string|htmldoc $html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->toArray() AS $item) {
				if ($item instanceof tag) {
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
	public function before(string|htmldoc $html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->toArray() AS $item) {
				if ($item instanceof tag) {
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
	public function after(string|htmldoc $html) : htmldoc {
		if (($nodes = $this->parse($html)) !== false) {
			foreach ($this->toArray() AS $item) {
				if ($item instanceof tag) {
					$item->after($nodes);
				}
			}
		}
		return $this;
	}

	/**
	 * Removes all top level nodes, or if $selector is specified, the nodes matched by the selector
	 *
	 * @param ?string $selector A CSS selector to refine the nodes to delete or null to delete top level nodes
	 * @return htmldoc The current htmldoc object with the requested nodes deleted
	 */
	public function remove(?string $selector = null) : htmldoc {
		$obj = $selector ? $this->search($selector, $this->toArray()) : $this;
		foreach ($obj->toArray() AS $item) {
			if ($item instanceof tag) {
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
	 * @return string|false The compiled HTML, or false if the file could not be saved
	 */
	public function save(?string $file = null, array $options = []) : string|false {

		// compile html
		$html = $this->html($options);

		// convert to the output charset if it differs from the internal encoding
		$charset = $options['charset'] ?? $this->config['output']['charset'];
		if (!empty($charset) && \strcasecmp($charset, \mb_internal_encoding()) !== 0) {
			$html = $this->convertCharset($html, $charset);
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
	protected function convertCharset(string $html, string $charset) : string {

		// convert, writing characters the charset cannot represent as numeric entities, then restore the global substitute setting
		$substitute = \mb_substitute_character();
		\mb_substitute_character('entity');
		try {
			$html = \mb_convert_encoding($html, $charset, \mb_internal_encoding());
		} finally {
			\mb_substitute_character($substitute);
		}
		return $html;
	}
}
