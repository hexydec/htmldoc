<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

/**
 * @property tag|null $parent
 * @property htmldoc $root
 * @property array $config
 * @property tag|null $parent
 * @property array $parenttags
 * @property string|null $tagName
 * @property array $attributes
 * @property string|null $singleton
 * @property array $children
 */
class tag implements token {

	/**
	 * @var htmldoc The parent htmldoc object
	 */
	protected htmldoc $root;

	/**
	 * @var array The object configuration
	 */
	protected array $config = [];

	/**
	 * @var tag|null The parent tag object
	 */
	protected ?tag $parent = null;

	/**
	 * @var array Cache for the list of parent tags
	 */
	protected array $parenttags = [];

	/**
	 * @var string The type of tag
	 */
	protected ?string $tagName = null;

	/**
	 * @var array An array of attributes where the key is the name of the attribute and the value is the value
	 */
	protected array $attributes = [];

	/**
	 * @var string|null If the tag is a singleton, this defines the closing string
	 */
	protected ?string $singleton = null;

	/**
	 * @var array An array of child token objects
	 */
	protected array $children = [];

	/**
	 * @var bool Whether to close the tag when rendering as HTML
	 */
	public bool $close = true;

	/**
	 * Constructs the token
	 *
	 * @param htmldoc $root The parent HTMLdoc object
	 * @param string $tag The HTML tag this object will represent
	 * @param tag $parent The parent tag object
	 */
	public function __construct(htmldoc $root, string $tag = null, tag $parent = null) {
		$this->root = $root;
		$this->tagName = $tag;
		$this->parent = $parent;
		$this->config = $this->root->config; // cache the config
		$this->close = !\in_array($tag, $this->config['elements']['closeoptional'], true);
	}

	/**
	 * Magic method to set protected variables
	 *
	 * @param string $name The name of the property to set
	 * @param mixed $value The value of the property to set
	 * @return void
	 */
	public function __set(string $name, $value) : void {
		if ($name === 'parent' && \get_class($value) === 'hexydec\\html\\tag') {
			$this->parent = $value;
		}
	}

	/**
	 * Magic method to clone the current object
	 *
	 * @return void
	 */
	public function __clone() {
		foreach ($this->children AS &$item) {
			$item = clone $item;
		}
		unset($item);
	}

	/**
	 * Parses the next HTML component from a tokenise object
	 *
	 * @param tokenise $tokens A tokenise object
	 * @return void
	 */
	public function parse(tokenise $tokens) : void {

		// cache vars
		$tag = $this->tagName;
		$attributes = [];

		// parse tokens
		$attr = null;
		while (($token = $tokens->next()) !== null) {
			switch ($token['type']) {

				// if you end up here, you are parsing an unclosed tag
				case 'tagopenstart':
					$tokens->prev();
					break 2;

				// remember attribute
				case 'attribute':
					if ($attr) {
						$attributes[$attr] = null;
						$attr = null;
					}
					$attr = \ltrim($token['value']);
					break;

				// record attribute and value
				case 'attributevalue':
					if ($attr) {
						$value = \trim($token['value'], "= \t\r\n");
						if (($pos = \strpos($value, '"')) === 0 || \strpos($value, "'") === 0) {
							$value = \trim($value, $pos === 0 ? '"' : "'");
						}
						$attributes[$attr] = \html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
						$attr = null;
					}
					break;

				case 'tagopenend':
					if (!\in_array($tag, $this->config['elements']['singleton'], true)) {
						$this->children = $this->parseChildren($tokens);
						break;
					} else {
						$this->singleton = $token['value'];
						break 2;
					}

				case 'tagselfclose':
					if (\in_array($tag, $this->config['elements']['singleton'], true)) {
						$this->singleton = $token['value'];
					}
					break 2;

				case 'tagclose':
					$close = \mb_strtolower(\trim($token['value'], "</ \r\n\t>"));

					// if tags same, we are closing this tag, go back to parent
					if (\in_array($close, $this->getParentTagNames(), true)) {

						// when it is not our tag, pass it to the parent to handle
						if ($close !== $tag) {
							$tokens->prev();

						// otherwise we are closing ourself
						} else {
							$this->close = true;
						}
						break 2;

					// ignore the closing tag
					} else {
						break;
					}
			}
		}
		if ($attr) {
			$attributes[$attr] = null;
		}
		if ($attributes) {
			$this->attributes = $attributes;

			// cache attribute for minifier
			$this->root->cache('attr', \array_keys($attributes));
			$attrvalues = [];
			foreach ($attributes AS $key => $item) {
				$attrvalues[] = $key.'='.$item;
			}
			$this->root->cache('attrvalues', $attrvalues);
		}
	}

	/**
	 * Retrieves an array of all the parent tag names of this node
	 *
	 * @return array An array of parent tag names
	 */
	protected function getParentTagNames() : array {
		if (empty($this->parenttags)) {
			$this->parenttags = $this->parent ? $this->parent->getParentTagNames() : [];
			if ($this->tagName !== null) {
				$this->parenttags[] = \mb_strtolower($this->tagName);
			}
		}
		return $this->parenttags;
	}

	/**
	 * Parses an array of tokens into an HTML documents
	 *
	 * @param tokenise &$tokens A reference to an array of tokens generated by tokenise(), the reference allows the array pointer to pass between objectsS
	 * @return array An array of child objects
	 */
	public function parseChildren(tokenise $tokens) : array {
		$root = $this->root;
		$parenttag = $this->tagName;
		$children = [];

		// process custom tags
		if ($parenttag && isset($this->config['custom'][$parenttag])) {
			$item = new $this->config['custom'][$parenttag]['class']($root, $parenttag);
			$item->parse($tokens);
			$children[] = $item;
			$this->close = true;

		// parse children
		} else {
			$optional = $this->config['elements']['closeoptional'];
			while (($token = $tokens->next()) !== null) {
				switch ($token['type']) {
					case 'doctype':
						$item = new doctype($root);
						$item->parse($tokens);
						$children[] = $item;
						break;

					case 'tagopenstart':
						$tag = \trim($token['value'], '<');

						// unnestable tag, pass back to parent
						if ($parenttag && \strcasecmp($tag, $parenttag) === 0 && \in_array($tag, $optional, true)) {
							$tokens->prev();
							break 2;
						} else {

							// parse the tag
							$item = new tag($root, $tag, $this);
							$item->parse($tokens);
							$children[] = $item;
						}
						break;

					case 'tagclose':
						$close = \trim($token['value'], "</ \r\n\t>");

						// prevent dropping down a level when tags don't match or close is optional
						if (\in_array(\mb_strtolower($close), $this->getParentTagNames(), true)) {
							$tokens->prev(); // let the parent parse() method handle it
							break 2;
						}
						break;

					case 'textnode':
						$item = new text($root, $this);
						$item->parse($tokens);
						$children[] = $item;
						break;

					case 'comment':
						$item = new comment($root);
						$item->parse($tokens);
						$children[] = $item;
						break;
				}
			}
		}
		return $children;
	}

	/**
	 * Returns the parent of the current object
	 *
	 * @return tag The parent tag
	 */
	public function parent() : ?tag {
		return $this->parent;
	}

	/**
	 * Append an array of nodes to the current children
	 *
	 * @param array $nodes An array of node objects
	 * @param int $index To insert the nodes at a particular position, set the index
	 * @return void
	 */
	public function append(array $nodes, int $index = null) : void {

		// reset the index if it doesn't exist
		if ($index !== null && !isset($this->children[$index])) {
			$index = null;
		}

		// clone the nodes
		$clones = [];
		foreach ($nodes AS $item) {
			$child = clone $item;
			$child->parent = $this;
			if ($index === null) {
				$this->children[] = $child;
			} else {
				$clones[] = $child;
			}
		}

		// insert the nodes
		if ($index !== null) {
			\array_splice($this->children, $index, 0, $clones);
		}
	}

	/**
	 * Prepend an array of nodes to the current children
	 *
	 * @param array $nodes An array of node objects
	 * @return void
	 */
	public function prepend(array $nodes) : void {
		foreach (\array_reverse($nodes) AS $item) {
			$child = clone $item;
			$child->parent = $this;
			\array_unshift($this->children, $child);
		}
	}

	/**
	 * Retrieve the index position of the current element in the parent
	 *
	 * @return int|null The index of the current element with the parent, or null if there is no parent
	 */
	protected function getIndex() : ?int {
		if ($this->parent) {
			foreach ($this->parent->children() AS $key => $item) {
				if ($item === $this) {
					return $key;
				}
			}
		}
		return null;
	}

	/**
	 * Insert an array of nodes before the current node
	 *
	 * @param array $nodes An array of node objects
	 * @return void
	 */
	public function before(array $nodes) : void {
		if ($this->parent !== null && ($index = $this->getIndex()) !== null) {
			$this->parent->append($nodes, $index);
		}
	}

	/**
	 * Insert an array of nodes after the current node
	 *
	 * @param array $nodes An array of node objects
	 * @return void
	 */
	public function after(array $nodes) : void {
		if ($this->parent !== null && ($index = $this->getIndex()) !== null) {
			$this->parent->append($nodes, $index + 1);
		}
	}

	/**
	 * Remove the selected child from the object
	 *
	 * @param tag $node The child object to delete
	 * @return void
	 */
	public function remove(tag $node) : void {
		foreach ($this->children AS $key => $item) {
			if ($item === $node) {
				$children = $this->children;
				unset($children[$key]);

				// re-key the values so the indexes are sequential
				$this->children = \array_values($children);
			}
		}
	}

	/**
	 * Minifies the internal representation of the tag
	 *
	 * @param array $minify An array of minification options controlling which operations are performed
	 * @return void
	 */
	public function minify(array $minify) : void {
		$config = $this->config;
		$attr = $config['attributes'];
		if ($minify['lowercase'] && $this->tagName) {
			$this->tagName = \mb_strtolower($this->tagName);
		}
		$folder = null;
		$dirs = null;
		$scheme = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 's' : '').'://';

		// minify attributes
		$tag = $this->tagName;
		$attributes = $this->attributes;
		$host = null;
		foreach ($attributes AS $key => $value) {

			// lowercase attribute key
			if ($minify['lowercase']) {
				unset($attributes[$key]);
				$key = \mb_strtolower(\strval($key));
				$attributes[$key] = $value;
			}

			// minify url attributes when not in list or match attribute
			if ($minify['urls'] && $attributes[$key] && \in_array($key, $attr['urls'], true) && (!\in_array($tag, \array_keys($attr['urlskip']), true) || $this->hasAttribute($attributes, $attr['urlskip'][$tag]))) {

				// make folder variables
				if ($folder === null && isset($_SERVER['REQUEST_URI'])) {
					if (($folder = \parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) !== null) {
						if (\mb_substr($folder, -1) !== '/') {
							$folder = \dirname($folder).'/';
						}
						$dirs = \explode('/', \trim($folder, '/'));
					}
				}

				// strip scheme from absolute URL's if the same as current scheme
				if ($minify['urls']['scheme'] && \mb_strpos($attributes[$key], $scheme) === 0) {
					$attributes[$key] = \mb_substr($attributes[$key], \mb_strlen($scheme)-2);
				}

				// remove host for own domain
				if ($minify['urls']['host'] && isset($_SERVER['HTTP_HOST'])) {
					$host = $host ?? ['//'.$_SERVER['HTTP_HOST'], $scheme.$_SERVER['HTTP_HOST']];
					foreach ($host AS $item) {

						// check if link goes to root
						if ($item === $attributes[$key]) {
							$attributes[$key] = $_SERVER['REQUEST_URI'] ? '/' : '';
							break;

						// remove host
						} else {
							$len = \mb_strlen($item);
							if (\mb_stripos($attributes[$key], $item) === 0 && (\mb_strlen($attributes[$key]) === $len || \mb_strpos($attributes[$key], '/', 2) === $len)) {
								$attributes[$key] = \mb_substr($attributes[$key], $len);
								break;
							}
						}
					}
				}

				// make absolute URLs relative
				if ($minify['urls']['relative'] && $folder) {

					// minify
					if (\mb_strpos($attributes[$key], $folder) === 0 && ($folder !== '/' || \mb_strpos($attributes[$key], '//') !== 0)) {
						if ($attributes[$key] === $folder && $attributes[$key] !== $_SERVER['REQUEST_URI']) {
							$attributes[$key] = './';
						} else {
							$attributes[$key] = \mb_substr($attributes[$key], \mb_strlen($folder));
						}
					}
				}

				// use parent folders if it is shorter
				if ($minify['urls']['parent'] && $dirs && \mb_strpos($attributes[$key], '/') === 0 && \mb_strpos($attributes[$key], '//') === false) {
					$isDir = \mb_strrpos($attributes[$key], '/') === \mb_strlen($attributes[$key])-1;
					$compare = \explode('/', \trim($isDir ? $attributes[$key] : \dirname($attributes[$key]), '/'));
					$update = false;
					$count = 0;
					foreach ($compare AS $i => $item) {
						if (isset($dirs[$i]) && $item === $dirs[$i]) {
							\array_shift($compare);
							$update = true;
							$count++;
						} else {
							break;
						}
					}
					if ($update) {
						$compare = \array_merge(\array_fill(0, \count($dirs) - $count, '..'), $compare);
						$url = \implode('/', $compare).'/'.($isDir ? '' : \basename($attributes[$key]));
						if (\strlen($url) <= \strlen($attributes[$key])) { // compare as bytes
							$attributes[$key] = $url;
						}
					}
				}
			}

			// minify attributes
			if ($minify['attributes']) {

				// trim attribute
				if ($minify['attributes']['trim'] && $attributes[$key]) {
					$attributes[$key] = \trim($attributes[$key], " \r\n\t");
				}

				// boolean attributes
				if ($minify['attributes']['boolean'] && \in_array($key, $attr['boolean'], true)) {
					$attributes[$key] = null;

				// minify style tag
				} elseif ($key === 'style' && $minify['attributes']['style']) {
					$attributes[$key] = \trim(\str_replace(
						['  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '],
						[' ', ':', ':', ':', ';', ';', ';'],
						$attributes[$key]
					), '; ');

				// trim classes
				} elseif ($key === 'class' && $minify['attributes']['class'] && \mb_strpos($attributes[$key], ' ') !== false) {
					$attributes[$key] = \trim(\preg_replace('/\s+/', ' ', $attributes[$key]));

				// minify option tag, always capture the tag to prevent it being removed as a default
				} elseif ($key === 'value' && $tag === 'option') {
					if ($minify['attributes']['option'] && isset($this->children[0]) && $this->children[0]->text() === $attributes[$key]) {
						unset($attributes[$key]);
					}
					continue;

				// remove tag specific default attribute
				} elseif ($minify['attributes']['default'] && isset($attr['default'][$tag][$key]) && ($attr['default'][$tag][$key] === true || $attr['default'][$tag][$key] === $attributes[$key])) {
					unset($attributes[$key]);
					continue;
				}

				// remove other attributes
				if ($attributes[$key] === '' && $minify['attributes']['empty'] && \in_array($key, $attr['empty'], true)) {
					unset($attributes[$key]);
					continue;
				}
			}
		}

		// minify singleton closing style
		if ($minify['singleton'] && $this->singleton) {
			$this->singleton = '>';
		}

		// work out whether to omit the closing tag
		if ($minify['close'] && $this->parent !== null && \in_array($tag, $config['elements']['closeoptional']) && ($this->parent->tagName === null || !\in_array($this->parent->tagName, $config['elements']['inline'], true))) {
			$children = $this->parent->toArray();
			$next = false;
			foreach ($children AS $item) {

				// find self in siblings
				if ($item === $this) {
					$next = true;

				// find next tag
				} elseif ($next) {
					$type = \get_class($item);

					// if type is not text or the text content is empty
					if ($type !== 'hexydec\\html\\text' || !$item->text()) {

						// if the next tag is optinally closable too, then we can remove the closing tag of this
						if ($type === 'hexydec\\html\\tag' && \in_array($item->tagName, $config['elements']['closeoptional'], true)) {
							$this->close = false;
						}

						// indicate we have process this
						$next = false;
						break;
					}
				}
			}

			// if last tag, remove closing tag
			if (empty($children) || $next) {
				$this->close = false;
			}
		}

		// sort attributes
		if (!empty($minify['attributes']['sort']) && $attributes) {
			$attributes = \array_replace(\array_intersect_key(\array_fill_keys($minify['attributes']['sort'], false), $attributes), $attributes);
		}
		$this->attributes = $attributes;

		// minify children
		if ($this->children) {

			// use tag specific minification options
			if (isset($minify['elements'][$tag])) {
				foreach ($minify AS $key => $item) {
					if (isset($minify['elements'][$tag][$key])) {
						if (!\is_array($minify['elements'][$tag][$key])) {
							$minify[$key] = $minify['elements'][$tag][$key];
						} elseif ($minify[$key]) {
							$minify[$key] = \array_merge($minify[$key], $minify['elements'][$tag][$key]);
						}
					}
				}
				// $minify = array_replace_recursive($minify, $minify['elements'][$tag]);
			}
			foreach ($this->children AS $item) {
				$item->minify($minify);
			}
		}
	}

	protected function hasAttribute(array $attr, array $items) {
		foreach ($items AS $key => $item) {
			if (!isset($attr[$key]) || !\in_array($attr[$key], $item, true)) {
				return false;
			}
		}
		return true;
	}
	/**
	 * Determine whether this tag or any of its child tokens match a selector
	 *
	 * @param array $selector An array of CSS selectors
	 * @param bool $searchChildren Denotes whether to search child tags as well as this tag
	 * @return array An array of tag objects that match $selector
	 */
	public function find(array $selector, bool $searchChildren = true) : array {
		$found = [];
		$match = true;
		foreach ($selector AS $i => $item) {

			// only search this level
			if ($item['join'] === '>' && !$i) {
				$searchChildren = false;
			}

			// pass rest of selector to level below
			if ($item['join'] && $i) {
				$match = false;
				foreach ($this->children AS $child) {
					if (\get_class($child) === 'hexydec\\html\\tag') {
						$found = \array_merge($found, $child->find(\array_slice($selector, $i)));
					}
				}
				break;

			// match tag
			} elseif (!empty($item['tag']) && $item['tag'] !== '*') {
				if ($item['tag'] !== $this->tagName) {
					$match = false;
					break;
				}

			// match id
			} elseif (!empty($item['id'])) {
				if (empty($this->attributes['id']) || $item['id'] !== $this->attributes['id']) {
					$match = false;
					break;
				}

			// match class
			} elseif (!empty($item['class'])) {
				if (empty($this->attributes['class']) || !\in_array($item['class'], \explode(' ', $this->attributes['class']), true)) {
					$match = false;
					break;
				}

			// attribute selector
			} elseif (!empty($item['attribute'])) {

				// check if attribute exists
				if (!array_key_exists($item['attribute'], $this->attributes)) {
					$match = false;
					break;
				} elseif (!empty($item['value'])) {

					// if current value is null, it won't match
					if (($current = $this->attributes[$item['attribute']]) === null) {
						$match = false;
						break;

					// compare
					} else {
						switch ($item['comparison']) {

							// exact match
							case '=':
								if ($item['sensitive']) {
									if ($current !== $item['value']) {
										$match = false;
										break;
									}
								} elseif (\mb_strtolower($current) !== \mb_strtolower($item['value'])) {
									$match = false;
									break;
								}
								break;

							// match start
							case '^=':
								$pos = $item['sensitive'] ? \mb_strpos($current, $item['value']) : \mb_stripos($current, $item['value']);
								if ($pos !== 0) {
									$match = false;
									break;
								}
								break;

							// match word
							case '~=':
								$current =' '.$current.' ';
								$item['value'] = ' '.$item['value'].' ';

							// match within
							case '*=':
								$pos = $item['sensitive'] ? \mb_strpos($current, $item['value']) : \mb_stripos($current, $item['value']);
								if ($pos === false) {
									$match = false;
									break;
								}
								break;

							// match end
							case '$=':
								$pos = $item['sensitive'] ? \mb_strrpos($current, $item['value']) : \mb_strripos($current, $item['value']);
								if ($pos !== \mb_strlen($current) - \mb_strlen($item['value'])) {
									$match = false;
									break;
								}
								break;

							// match subcode
							case '|=':
								if ($item['sensitive']) {
									if ($current !== $item['value'] && \mb_strpos($current, $item['value'].'-') !== 0) {
										$match = false;
										break;
									}
								} elseif (\mb_strtolower($current) !== \mb_strtolower($item['value']) && \mb_stripos($current, $item['value'].'-') !== 0) {
									$match = false;
									break;
								}
								break;
						}
					}
				}

			// match pseudo selector
			} elseif (!empty($item['pseudo'])) {
				switch ($item['pseudo']) {

					// match first-child
					case 'first-child':
						$children = $this->parent !== null ? $this->parent->children() : [];
						if (!isset($children[0]) || $this !== $children[0]) {
							$match = false;
							break 2;
						}
						break;

					// match last child
					case 'last-child':
						$children = $this->parent !== null ? $this->parent->children() : [];
						if (($last = \end($children)) === false || $this !== $last) {
							$match = false;
							break 2;
						}
						break;

					// match not
					case 'not':
						if (!empty($item['sub'])) {
							foreach ($item['sub'] AS $sub) {
								if ($this->find($sub, false) !== []) {
									$match = false;
									break 3;
								}
							}
						}
						break;
				}
			}
		}
		if ($match) {
			$found[] = $this;
		}
		if ($searchChildren && $this->children) {
			foreach ($this->children AS $child) {
				if (\get_class($child) === 'hexydec\\html\\tag') {
					$found = \array_merge($found, $child->find($selector));
				}
			}
		}
		return $found;
	}

	/**
	 * Retrieve the specified attribute from the tag or update its value
	 *
	 * @param string $key The key of the attribute whos value you wish to retrieve or update
	 * @param string $value The value of the attribute to update
	 * @return string|null The value of the attrbute or NULL if the attribute does not exist
	 */
	public function attr(string $key, ?string $value = null) : ?string {

		// set the value
		if ($value !== null) {
			$this->attributes[$key] = $value;

		// get the value
		} else {
			return $this->attributes[$key] ?? null;
		}
		return null;
	}

	/**
	 * Retrievves the value of the text nodes contained within the object, multiple values are concatenated with a space
	 *
	 * @return string The text string from this tag's child objects
	 */
	public function text() : string {
		$text = [];
		foreach ($this->children AS $item) {

			// only get text from these objects
			if (\in_array(\get_class($item), ['hexydec\\html\\tag', 'hexydec\\html\\text'])) {
				$text[] = $item->text();
			}
		}
		return \implode(' ', $text);
	}

	/**
	 * Compile the tag as an HTML string
	 *
	 * @param array $options An array indicating output options
	 * @return string The compiled HTML
	 */
	public function html(array $options = []) : string {
		$tag = $this->tagName;

		// merge output options + custom
		$output = $this->config['output'];
		$options = \array_merge($output, $options, $output['elements'][$tag] ?? []);

		// compile attributes
		$html = '<'.$tag;
		foreach ($this->attributes AS $key => $value) {
			$html .= ' '.$key;
			if ($value !== null || $options['xml']) {
				$empty = \in_array($value, [null, ''], true);

				// unquoted
				if (!$empty && !$options['xml'] && $options['quotestyle'] === 'minimal' && \strpbrk($value, " =\"'`<>\n\r\t") === false) {
					$html .= '='.$value;

				// single quotes || swap when minimal and there are double quotes in the string
				} elseif ($options['quotestyle'] === 'single' || ($options['quotestyle'] === 'minimal' && \mb_strpos($value, '"') !== false)) {
					$html .= "='".\str_replace(['&', "'", '<'], ['&amp;', '&#39;', '&lt;'], $value)."'";

				// double quotes
				} else {
					$html .= '="'.\str_replace(['&', '"', '<'], ['&amp;', '&quot;', '&lt;'], \strval($value)).'"';
				}
			}
		}

		// close singleton tags
		if ($this->singleton) {
			$html .= empty($options['singletonclose']) ? $this->singleton : $options['singletonclose'];

		// close opening tag and compile contents
		} else {
			$html .= '>';
			foreach ($this->children AS $item) {
				$html .= $item->html($options);
			}
			if ($options['closetags'] || $this->close) {
				$html .= '</'.$tag.'>';
			}
		}
		return $html;
	}

	/**
	 * Retrieves the child tokens as an array
	 *
	 * @return array An array of tokens
	 */
	public function toArray() : array {
		return $this->children;
	}

	/**
	 * Retrieves the child tag objects as an array
	 *
	 * @return array An array of tag objects
	 */
	public function children() : array {
		$children = [];
		foreach ($this->children AS $item) {
			if (\get_class($item) === 'hexydec\\html\\tag') {
				$children[] = $item;
			}
		}
		return $children;
	}

	/**
	 * Retrieves the requested object property
	 *
	 * @return mixed The value of the requested property
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $var) {
		return $this->$var;
	}
}
