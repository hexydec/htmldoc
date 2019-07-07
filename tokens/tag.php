<?php
namespace hexydec\html;

class tag {

	protected $config = Array();
	protected $tagName;
	protected $attributes = Array();
	protected $singleton = false;
	protected $children;
	public $close = true;

	public function __construct($tag, $config) {
		$this->tagName = $tag;
		$this->config = $config;
	}

	public function parse(Array &$tokens, array &$attach = null) {
		$attr = false;
		while (($token = next($tokens)) !== false) {
			switch ($token['type']) {

				// remember attribute
				case 'attribute':
					if ($attr) {
						$this->attributes[$attr] = null;
						$attr = false;
					}
					$attr = $token['value'];

					// cache attribute for minifier
					//$this->attributes[$attr] = isset($this->attributes[$attr]) ? $this->attributes[$attr] + 1 : 1;
					break;

				// record attribute and value
				case 'attributevalue':
					if ($attr) {
						$this->attributes[$attr] = html_entity_decode(trim(trim($token['value'], '= '), '"'), ENT_QUOTES); // set charset?
						$attr = false;
					}
					break;

				case 'tagopenend':
					next($tokens);
					$this->children = new htmldoc($this->config);
					$this->children->parse($tokens, $this->tagName, $attach);
					break 2;

				case 'tagselfclose':
					break 2;
			}
		}
		if ($attr) {
			$this->attributes[$attr] = null;
			$attr = false;
		}
	}

	public function minify(Array $config, object $parent = null) {
		$attr = $this->config['attributes'];
		if ($config['lowercase']) {
			$this->tagName = strtolower($this->tagName);
		}

		// minify attributes
		$folder = false;
		foreach ($this->attributes AS $key => $value) {

			// lowercase attribute key
			if ($config['lowercase']) {
				unset($this->attributes[$key]);
				$key = strtolower($key);
				$this->attributes[$key] = $value;
			}

			// minify attributes
			if ($config['attributes']) {

				// trim attribute
				$value = $this->attributes[$key] = trim($value);

				// boolean attributes
				if ($config['attributes']['boolean'] && in_array($key, $attr['boolean'])) {
					$this->attributes[$key] = null;

				// minify style tag
				} elseif ($key == 'style' && $config['attributes']['style']) {
					$this->attributes[$key] = trim(str_replace(
						Array('  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '),
						Array(' ', ':', ':', ':', ';', ';', ';'),
						$value
					), '; ');

				// sort classes
				} elseif ($key == 'class' && $config['attributes']['class'] && strpos($value, ' ') !== false) {
					$class = array_filter(explode(' ', $value));
					sort($class);
					$this->attributes[$key] = implode(' ', $class);

				// minify option tag
				} elseif ($key == 'value' && $config['attributes']['option'] && $this->tagName == 'option' && isset($this->children[0]) && $this->children[0]->text() == $value) {
					unset($this->attributes[$key]);

				// remove tag specific default attribute
				} elseif ($config['attributes']['default'] && isset($attr['default'][$this->tagName][$key]) && ($attr['default'][$this->tagName][$key] === true || $attr['default'][$this->tagName][$key] == $value)) {
					unset($this->attributes[$key]);
				}

				// remove other attributes
				if ($value === '' && $config['attributes']['empty'] && in_array($key, $attr['empty'])) {
					unset($this->attributes[$key]);
				}
			}

			// minify urls
			if ($config['urls'] && in_array($key, $attr['urls'])) {

				// strip scheme from absolute URL's if the same as current scheme
				if ($config['urls']['scheme']) {
					if (!isset($scheme)) {
						$scheme = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://';
					}
					if (strpos($this->attributes[$key], $scheme) === 0) {
						$this->attributes[$key] = substr($this->attributes[$key], strlen($scheme)-2);
					}
				}

				// remove host for own domain
				if ($config['urls']['host']) {
					if (!isset($host)) {
						$host = '//'.$_SERVER['HTTP_HOST'];
						$hostlen = strlen($host);
					}
					if (strpos($this->attributes[$key], $host) === 0 && (strlen($this->attributes[$key]) == $hostlen || strpos($this->attributes[$key], '/', 2)) == $hostlen + 1) {
						$this->attributes[$key] = substr($this->attributes[$key], $hostlen);
					}
				}

				// make absolute URLs relative
				if ($config['urls']['absolute']) {

					// set folder variable
					if (!$folder) {
						$folder = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
						if (substr($folder, -1) != '/') {
							$folder = dirname($folder).'/';
						}
					}

					// minify
					if (strpos($this->attributes[$key], $folder) === 0) {
						$this->attributes[$key] = substr($this->attributes[$key], strlen($folder));
					}
				}
			}
		}

		// work out whether to omit the closing tag
		if ($config['close'] && in_array($this->tagName, $this->config['elements']['closeoptional'])) {
			$tag = null;
			$children = $parent->children();
			$last = end($children);
			$next = false;
			foreach ($children->toArray() AS $item) {

				// find self in siblings
				if ($item === $this) {
					$next = true;

				// find next tag
				} elseif ($next) {
					$type = get_class($item);

					// if type is not text or the text content is empty
					if ($type != 'hexydec\\html\\text' || !$item->content) {

						// if the next tag is optinally closable too, then we can remove the closing tag of this
						if ($type == 'hexydec\\html\\tag' && in_array($item->tagName, $this->config['elements']['closeoptional'])) {
							$this->close = false;
						}

						// indicate we have process this
						$next = false;
						break;
					}
				}
			}

			// if last tag, remove closing tag
			if ($next) {
				$this->close = false;
			}
		}

		// sort attributes
		// if ($config['attributes']['sort']) {
		// 	$attr = $this->attributes;
		// 	$this->attributes = Array();
		// 	foreach ($config['attributes']['sort'] AS $key) {
		// 		if (isset($attr[$key])) {
		// 			$this->attributes[$key] = $attr[$key];
		// 		}
		// 	}
		// }

		// minify children
		if ($this->children) {
			$this->children->minify($config, $this);
		}
	}

	public function find(Array $selector) : Array {
		$found = Array();
		$match = true;
		$searchChildren = true;
		foreach ($selector AS $i => $item) {

			// only search this level
			if ($item['join'] == '>' && !$i) {
				$searchChildren = false;
			}

			// pass rest of selector to level below
			if ($item['join'] && $i) {
				$match = false;
				if (($children = $this->children->find(Array(array_slice($selector, $i)))) !== false) {
					$found = array_merge($found, $children);
				}
				break;
			} elseif (!empty($item['tag']) && $item['tag'] != '*') {
				if ($item['tag'] != $this->tagName) {
					$match = false;
					break;
				}
			} elseif (!empty($item['id'])) {
				if (empty($this->attributes['id']) || $item['id'] != $this->attributes['id']) {
					$match = false;
					break;
				}
			} elseif (!empty($item['class'])) {
				if (empty($this->attributes['class']) || !in_array($item['class'], explode(' ', $this->attributes['class']))) {
					$match = false;
					break;
				}
			} elseif (!empty($item['attribute'])) {
				if (empty($this->attributes[$item['attribute']])) {
					$match = false;
					break;
				} elseif (!empty($item['value'])) {
					if ($item['comparison'] == '=') {
						if ($this->attributes[$item['attribute']] != $item['value']) {
							$match = false;
							break;
						}
					} elseif ($item['comparison'] == '^=') {
						if (strpos($item['value'], $this->attributes[$item['attribute']]) !== 0) {
							$match = false;
							break;
						}
					} elseif ($item['comparison'] == '$=') {
						if (strpos($item['value'], $this->attributes[$item['attribute']]) !== strlen($this->attributes[$item['attribute']]) - strlen($item['value'])) {
							$match = false;
							break;
						}
					}
				}
			}
		}
		if ($match) {
			$found[] = $this;
		}
		if ($searchChildren && $this->children && ($children = $this->children->find(Array($selector))) !== false) {
			$found = array_merge($found, $children->toArray());
		}
		return $found;
	}

	public function attr(string $key) : ?string {
		if (isset($this->attributes[$key])) {
			return $this->attributes[$key];
		}
	}

	public function text() : string {
		if ($this->children) {
			return $this->children->text();
		} else {
			return '';
		}
	}

	public function compile(Array $config) : String {
		$html = '<'.$this->tagName;

		// compile attributes
		foreach ($this->attributes AS $key => $value) {
			$html .= ' '.$key;
			if ($value !== null) {
				$quote = '"';
				if ($config['quotestyle'] == 'single') {
					$quote = "'";
				} elseif ($value && $config['quotestyle'] == 'minimal' && strcspn($value, " =\"'`<>\n\r\t/") == strlen($value)) {
					$quote = '';
				}
				$html .= '='.$quote.htmlspecialchars($value).$quote;
			}
		}

		// close singleton tags
		if (in_array($this->tagName, $this->config['elements']['singleton'])) {
			$html .= $config['singletonclose'];

		// close opening tag and compile contents
		} else {
			$html .= '>';
			$html .= $this->children->compile($config);
			if ($config['closetags'] || $this->close) {
				$html .= '</'.$this->tagName.'>';
			}
		}
		return $html;
	}

	public function children() {
		return $this->children;
	}

	public function __get($var) {
		return $this->$var;
	}
}
