<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

/**
 * @property htmldoc $root
 * @property array $config
 * @property array $parenttags
 * @property string|null $tagName
 * @property string|null $tagNameLower
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
	public ?tag $parent = null;

	/**
	 * @var array Cache for the list of parent tags
	 */
	protected array $parenttags = [];

	/**
	 * @var string The type of tag
	 */
	protected ?string $tagName = null;

	/**
	 * @var string The lowercase tag name, for case-insensitive comparison
	 */
	protected ?string $tagNameLower = null;

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
	public function __construct(htmldoc $root, ?string $tag = null, ?tag $parent = null) {
		$this->root = $root;
		$this->tagName = $tag;
		$this->tagNameLower = $tag === null ? null : \strtolower($tag);
		$this->parent = $parent;
		$this->config = $this->root->config; // cache the config
		$this->close = $tag === null || $root->lookup('closeoptional', $this->tagNameLower) === null;
	}

	/**
	 * Magic method to set protected variables
	 *
	 * @param string $name The name of the property to set
	 * @param mixed $value The value of the property to set
	 * @return void
	 */
	public function __set(string $name, $value) : void {
		if ($name === 'children' && \is_array($value)) {
			$this->children = $value;
		}
	}

	/**
	 * Magic method to clone the current object
	 *
	 * @return void
	 */
	public function __clone() : void {
		foreach ($this->children AS &$item) {
			$item = clone $item;
			$item->parent = $this;
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
		$tag = $this->tagNameLower;
		$singleton = $this->root->lookup('singleton', $tag) !== null;
		$attributes = [];

		// parse tokens
		$attr = null;
		while (($token = $tokens->next()) !== null) {
			switch ($token['type']) {

				// if you end up here, you are parsing an unclosed tag
				case 'tagopenstart':
					$tokens->prev();
					break 2;

				// remember attribute, recording the previous one without a value, duplicates keep the first as browsers do
				case 'attribute':
					if ($attr && !\array_key_exists($attr, $attributes)) {
						$attributes[$attr] = null;
					}
					$attr = \ltrim($token['value']);
					break;

				// record attribute and value
				case 'attributevalue':
					if ($attr) {
						if (!\array_key_exists($attr, $attributes)) {
							$value = \ltrim(\substr($token['value'], \strpos($token['value'], '=') + 1), " \t\r\n\f");
							if (($double = \str_starts_with($value, '"')) === true || \str_starts_with($value, "'")) {
								$value = \trim($value, $double ? '"' : "'");
							}
							$attributes[$attr] = \html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
						}
						$attr = null;
					}
					break;

				case 'tagopenend':
					if (!$singleton) {
						$this->children = $this->parseChildren($tokens);
						break;
					} else {
						$this->singleton = $token['value'];
						break 2;
					}

				case 'tagselfclose':
					if ($singleton) {
						$this->singleton = $token['value'];
					}
					break 2;

				case 'tagclose':
					$close = \strtolower(\trim($token['value'], "</ \r\n\t>"));

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
		if ($attr && !\array_key_exists($attr, $attributes)) {
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
			if ($this->tagNameLower !== null) {
				$this->parenttags[] = $this->tagNameLower;
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
		$parenttag = $this->tagNameLower;
		$children = [];

		// process custom tags
		if ($parenttag && ($custom = $root->lookup('custom', $parenttag)) !== null) {
			$item = new $custom['class']($root, $parenttag, $this);
			$item->parse($tokens);
			$children[] = $item;
			$this->close = true;

		// parse children
		} else {
			$closers = $parenttag === null ? [] : ($root->lookup('closeoptional', $parenttag) ?? []);
			while (($token = $tokens->next()) !== null) {
				switch ($token['type']) {
					case 'doctype':
						$item = new doctype($root, $this);
						$item->parse($tokens);
						$children[] = $item;
						break;

					case 'tagopenstart':
						$tag = \trim($token['value'], '<');
						$lower = \strtolower($tag);

						// a tag that implicitly closes the parent, pass back to the parent
						if (\in_array($lower, $closers, true)) {
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
						if (\in_array(\strtolower($close), $this->getParentTagNames(), true)) {
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
						$item = new comment($root, $this);
						$item->parse($tokens);
						$children[] = $item;
						break;

					case 'cdata':
						$item = new cdata($root, $this);
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
	 * @return ?tag The parent tag
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
	public function append(array $nodes, ?int $index = null) : void {

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
			foreach ($this->parent->children AS $key => $item) {
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
			$this->tagName = $this->tagNameLower;
		}

		// minify attributes
		$tag = $this->tagNameLower;
		$attributes = $this->attributes;
		$skipurl = isset($attr['urlskip'][$tag]) && !$this->hasAttribute($attributes, $attr['urlskip'][$tag]);
		foreach ($attributes AS $key => $value) {

			// lowercase attribute key
			if ($minify['lowercase']) {
				unset($attributes[$key]);
				$key = \mb_strtolower(\strval($key));
				$attributes[$key] = $value;
			}

			// minify url attributes when not in list or match attribute
			if ($minify['urls'] && $value && \in_array($key, $attr['urls'], true) && !$skipurl && ($page = $this->root->getCurrentUrl()) !== null) {
				$attributes[$key] = $this->minifyUrl($attributes[$key], $page, $minify['urls']);
			}

			// minify attributes
			if ($minify['attributes']) {

				// cache attributes
				$min = $minify['attributes'];

				// trim attribute
				if ($min['trim'] && $attributes[$key]) {
					$attributes[$key] = \trim($attributes[$key], " \r\n\t");
				}

				// boolean attributes
				if ($min['boolean'] && \in_array($key, $attr['boolean'], true)) {
					$attributes[$key] = null;

				// boolean attributes that also take keyword values, minified only when the value is empty or the attribute name
				} elseif ($min['boolean'] && \in_array($key, $attr['booleanvalues'], true) && \in_array(\strtolower($attributes[$key] ?? ''), ['', $key], true)) {
					$attributes[$key] = null;

				// remove empty attributes
				} elseif (\in_array($attributes[$key], ['', null], true) && $min['empty'] && \in_array($key, $attr['empty'], true)) {
					unset($attributes[$key]);

				// minify style tag
				} elseif ($key === 'style' && $min['style'] && $attributes[$key]) {
					$attributes[$key] = \trim(\str_replace(
						["\t", "\r", "\n", '  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '],
						[' ', '', ' ', ' ', ':', ':', ':', ';', ';', ';'],
						$attributes[$key]
					), '; ');

				// trim classes
				} elseif ($key === 'class' && $min['class'] && \str_contains($attributes[$key] ?? '', ' ')) {
					$attributes[$key] = \trim(\preg_replace('/\s+/', ' ', $attributes[$key]));

				// minify option tag, always capture the tag to prevent it being removed as a default
				} elseif ($key === 'value' && $tag === 'option') {
					if ($min['option'] && isset($this->children[0]) && $this->children[0]->text() === $attributes[$key]) {
						unset($attributes[$key]);
					}

				// remove tag specific default attribute
				} elseif ($min['default'] && isset($attr['default'][$tag][$key]) && ($attr['default'][$tag][$key] === true || $attr['default'][$tag][$key] === $attributes[$key])) {
					unset($attributes[$key]);
				}
			}
		}

		// minify singleton closing style
		if ($minify['singleton'] && $this->singleton) {
			$this->singleton = '>';
		}

		// work out whether to omit the closing tag
		$parent = $this->parent?->tagNameLower;
		if ($minify['close'] && $this->parent !== null && ($closers = $this->root->lookup('closeoptional', $tag)) !== null && ($parent === null || ($this->root->lookup('inline', $parent) === null && $this->root->lookup('inlineblock', $parent) === null))) {
			$children = $this->parent->toArray();
			$next = false;
			foreach ($children AS $item) {

				// find self in siblings
				if ($item === $this) {
					$next = true;

				// find next tag
				} elseif ($next) {

					// stop at the next node that is not whitespace only text
					if (!$item instanceof text || \trim($item->text(), " \t\r\n") !== '') {

						// if the next tag implicitly closes this one, the closing tag can be removed
						if ($item instanceof tag && \in_array($item->tagNameLower, $closers, true)) {
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

	/**
	 * Shortens a URL relative to the current page
	 *
	 * @param string $url The URL to minify
	 * @param array $page The parsed URL of the current page, as returned by htmldoc::getCurrentUrl()
	 * @param array $options The URL minification options
	 * @return string The minified URL
	 */
	protected function minifyUrl(string $url, array $page, array $options) : string {

		// strip the scheme if it is the same as the current page
		$scheme = $page['scheme'];
		if ($options['scheme'] && \strncasecmp($url, $scheme, \strlen($scheme)) === 0) {
			$url = \substr($url, \strlen($scheme) - 2);
		}

		// remove the host for the current domain, leaving an absolute path
		if ($options['host']) {
			foreach (['//'.$page['host'], $scheme.$page['host']] AS $item) {
				$len = \strlen($item);
				if (\strncasecmp($url, $item, $len) === 0 && \in_array($url[$len] ?? '', ['', '/', '?', '#'], true)) {
					$url = \substr($url, $len);
					if (!\str_starts_with($url, '/')) {
						$url = '/'.$url;
					}
					break;
				}
			}
		}

		// make absolute paths relative to the current page
		if (($options['relative'] || $options['parent']) && \str_starts_with($url, '/') && !\str_starts_with($url, '//')) {

			// split the path into folders and a file, keeping the query string and fragment separate
			$split = \strcspn($url, '?#');
			$path = \substr($url, 0, $split);
			$suffix = \substr($url, $split);
			$pos = \strrpos($path, '/');
			$dirs = $pos === 0 ? [] : \explode('/', \substr($path, 1, $pos - 1));
			$file = \substr($path, $pos + 1);

			// count the folders in common with the current page
			$common = 0;
			foreach ($page['dirs'] AS $i => $item) {
				if (($dirs[$i] ?? null) === $item) {
					$common++;
				} else {
					break;
				}
			}
			$up = \count($page['dirs']) - $common;

			// build the relative URL when within the current folder, or the parent URL when above it
			if ($up ? $options['parent'] : $options['relative']) {
				$remaining = \array_slice($dirs, $common);
				$relative = \str_repeat('../', $up).($remaining ? \implode('/', $remaining).'/' : '').$file.$suffix;

				// a URL to the current page can be just the query string or fragment, as these resolve against the page
				if ($path === $page['path'] && (\str_starts_with($suffix, '?') || $page['query'] === null)) {
					$relative = $suffix;

				// otherwise a URL with no path resolves against the page rather than its folder, so needs ./
				} elseif (!$up && !$remaining && $file === '') {
					$relative = './'.$suffix;
				}

				// use the relative URL if it is not longer, compared as bytes
				if (\strlen($relative) <= \strlen($url)) {
					$url = $relative;
				}
			}
		}
		return $url;
	}

	/**
	 * Determines whether each of the specified attributes has one of its allowed values
	 *
	 * @param array $attr The attributes of the tag
	 * @param array $items An array of allowed values, keyed by attribute name
	 * @return bool Whether every attribute is present with an allowed value
	 */
	protected function hasAttribute(array $attr, array $items) : bool {
		foreach ($items AS $key => $item) {
			$lower = \mb_strtolower($key);
			if (!isset($attr[$lower]) || !\in_array($attr[$lower], $item, true)) {
				return false;
			}
		}
		return true;
	}
	/**
	 * Adds this tag and any of its descendants that match the selectors to the found tags, in document order
	 *
	 * @param array $groups An array of compiled selector groups, as returned by selector::get()
	 * @param array &$found A reference to the array the matching tags are added to
	 * @param bool $start Whether this tag is where the search starts, which a leading child combinator is relative to
	 * @return void
	 */
	public function find(array $groups, array &$found, bool $start = true) : void {

		// add this tag if it matches any of the selectors, a leading child combinator only matches where the search starts
		foreach ($groups AS $compounds) {
			if (($start || $compounds[0]['join'] !== '>') && $this->matches($compounds)) {
				$found[] = $this;
				break;
			}
		}

		// search the child tags
		foreach ($this->children AS $child) {
			if ($child instanceof tag) {
				$child->find($groups, $found, false);
			}
		}
	}

	/**
	 * Determines whether this tag matches a compiled selector group, matching the last compound against this tag and the earlier ones against the tags its combinators point to
	 *
	 * @param array $compounds An array of compounds, each with the combinator that precedes it and its simple selectors
	 * @param ?int $index The index of the compound to match against this tag, or null for the last
	 * @return bool Whether this tag matches
	 */
	public function matches(array $compounds, ?int $index = null) : bool {
		$index ??= \count($compounds) - 1;
		$match = false;

		// the document root is not a tag so never matches
		if ($this->tagName !== null && $this->matchesCompound($compounds[$index]['selectors'])) {

			// the first compound has nothing before it to check
			if ($index === 0) {
				$match = true;

			// the parent must match the previous compound
			} elseif ($compounds[$index]['join'] === '>') {
				$match = $this->parent?->matches($compounds, $index - 1) ?? false;

			// the previous sibling, or any earlier sibling, must match the previous compound
			} elseif (\in_array($compounds[$index]['join'], ['+', '~'], true)) {
				foreach (\array_reverse($this->getPreviousSiblings()) AS $sibling) {
					if ($sibling->matches($compounds, $index - 1)) {
						$match = true;
						break;
					} elseif ($compounds[$index]['join'] === '+') {
						break;
					}
				}

			// any ancestor must match the previous compound
			} else {
				$parent = $this->parent;
				while ($parent !== null && !$match) {
					$match = $parent->matches($compounds, $index - 1);
					$parent = $parent->parent;
				}
			}
		}
		return $match;
	}

	/**
	 * Retrieves the sibling tags that come before this tag
	 *
	 * @return array An array of tag objects in document order
	 */
	protected function getPreviousSiblings() : array {
		$siblings = [];
		foreach ($this->parent?->children() ?? [] AS $item) {
			if ($item === $this) {
				break;
			}
			$siblings[] = $item;
		}
		return $siblings;
	}

	/**
	 * Determines whether this tag matches all the simple selectors in a compound
	 *
	 * @param array $selectors An array of simple selectors
	 * @return bool Whether this tag matches every simple selector
	 */
	protected function matchesCompound(array $selectors) : bool {
		$match = true;
		foreach ($selectors AS $item) {
			if (!$this->matchesPart($item)) {
				$match = false;
				break;
			}
		}
		return $match;
	}

	/**
	 * Determines whether this tag matches a simple selector
	 *
	 * @param array $item A simple selector, specifying a tag, id, class, attribute, or pseudo selector
	 * @return bool Whether this tag matches the simple selector
	 */
	protected function matchesPart(array $item) : bool {

		// match tag
		if (isset($item['tag'])) {
			$match = $item['tag'] === '*' || $item['tag'] === $this->tagNameLower;

		// match id
		} elseif (isset($item['id'])) {
			$match = ($this->attributes['id'] ?? null) === $item['id'];

		// match class, which can be separated by any whitespace
		} elseif (isset($item['class'])) {
			$match = ($class = $this->attributes['class'] ?? null) !== null && \in_array($item['class'], \preg_split('/[ \\t\\n\\r\\f]++/', $class), true);

		// match attribute
		} elseif (isset($item['attribute'])) {
			$match = $this->matchesAttribute($item);

		// match pseudo selector
		} elseif (isset($item['pseudo'])) {
			$match = $this->matchesPseudo($item);

		// a selector with nothing to compare matches anything
		} else {
			$match = true;
		}
		return $match;
	}

	/**
	 * Determines whether this tag matches an attribute selector
	 *
	 * @param array $item An attribute selector, with the attribute name, and optionally a comparison, value, and case sensitivity
	 * @return bool Whether this tag matches the attribute selector
	 */
	protected function matchesAttribute(array $item) : bool {
		$name = $item['attribute'];
		$comparison = $item['comparison'] ?? null;

		// the attribute must exist, and when a value is compared, have a value
		if (!\array_key_exists($name, $this->attributes)) {
			$match = false;
		} elseif (!isset($item['value'])) {
			$match = true;
		} elseif (($current = $this->attributes[$name]) === null) {
			$match = false;

		// an empty value never matches the start, end, within, or word comparisons
		} elseif ($item['value'] === '' && \in_array($comparison, ['^=', '$=', '*=', '~='], true)) {
			$match = false;

		// compare the value, lowercasing both when case insensitive
		} else {
			$value = $item['value'];
			if (!$item['sensitive']) {
				$current = \mb_strtolower($current);
				$value = \mb_strtolower($value);
			}
			$match = match ($comparison) {
				'=' => $current === $value,
				'^=' => \str_starts_with($current, $value),
				'$=' => \str_ends_with($current, $value),
				'*=' => \str_contains($current, $value),
				'~=' => \in_array($value, \preg_split('/[ \\t\\n\\r\\f]++/', $current), true),
				'|=' => $current === $value || \str_starts_with($current, $value.'-'),
				default => false
			};
		}
		return $match;
	}

	/**
	 * Determines whether this tag matches a pseudo selector
	 *
	 * @param array $item A pseudo selector, with the pseudo class name and any compiled selector groups it contains
	 * @return bool Whether this tag matches the pseudo selector
	 */
	protected function matchesPseudo(array $item) : bool {
		switch ($item['pseudo']) {

			// match first child
			case 'first-child':
				$siblings = $this->parent?->children() ?? [];
				$match = ($siblings[0] ?? null) === $this;
				break;

			// match last child
			case 'last-child':
				$siblings = $this->parent?->children() ?? [];
				$match = ($siblings[\count($siblings) - 1] ?? null) === $this;
				break;

			// match none of the selectors
			case 'not':
				$match = true;
				foreach ($item['sub'] ?? [] AS $compounds) {
					if ($this->matches($compounds)) {
						$match = false;
						break;
					}
				}
				break;

			// unsupported pseudo selectors never match
			default:
				$match = false;
		}
		return $match;
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
			if ($item instanceof tag || $item instanceof text || $item instanceof cdata) {
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
		$options = \array_merge($output, $options, $output['elements'][$this->tagNameLower] ?? []);

		// compile attributes
		$html = '<'.$tag;
		foreach ($this->attributes AS $key => $value) {
			$html .= ' '.$key;
			if ($value !== null || $options['xml']) {
				$empty = \in_array($value, [null, ''], true);

				// encode the value for every quote style, each style then only escapes its own quote character
				$value = \str_replace(['&', '<'], ['&amp;', '&lt;'], \strval($value));

				// unquoted
				if (!$empty && !$options['xml'] && $options['quotestyle'] === 'minimal' && \strpbrk($value, " =\"'`<>\n\r\t\f") === false) {
					$html .= '='.$value;

				// single quotes || swap when minimal and there are double quotes in the string
				} elseif ($options['quotestyle'] === 'single' || ($options['quotestyle'] === 'minimal' && \str_contains($value, '"'))) {
					$html .= "='".\str_replace("'", '&#39;', $value)."'";

				// double quotes
				} else {
					$html .= '="'.\str_replace('"', '&quot;', $value).'"';
				}
			}
		}

		// close singleton tags
		if ($this->singleton) {
			$html .= empty($options['singletonclose']) ? $this->singleton : $options['singletonclose'];

		// self-close elements with no children in XML
		} elseif ($options['xml'] && !$this->children) {
			$html .= '/>';

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
			if ($item instanceof tag) {
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
	public function __get(string $var) : mixed {
		return $this->$var;
	}
}
