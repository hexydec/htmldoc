<?php
namespace hexydec\html;

class tag {

	protected $config = Array();
	protected $tagName;
	protected $attributes = Array();
	protected $children;
	protected $singleton = false;
	protected $quotes = 'double';

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
						$this->attributes[$attr] = html_entity_decode(trim($token['value'], '= "'), ENT_QUOTES); // set charset?
						$attr = false;
					}
					break;

				case 'tagopenend':
					next($tokens);
					$this->children = new collection($this->config);
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
				$this->attributes[$key] = trim($value);

				// boolean attributes
				if ($config['attributes']['boolean'] && in_array($key, $this->config['elements']['booleanattributes'])) {
					$this->attributes[$key] = null;
				}

				// minify style tag
				if ($key == 'style' && $config['attributes']['style']) {
					$this->attributes[$key] = trim(str_replace(
						Array('  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '),
						Array(' ', ':', ':', ':', ';', ';', ';'),
						$value
					), '; ');
				}

				// sort classes
				if ($key == 'class' && $config['attributes']['class'] && strpos($value, ' ') !== false) {
					$class = array_filter(explode(' ', $value));
					sort($class);
					$this->attributes[$key] = implode(' ', $class);
				}

				// minify option tag
				if ($key == 'value' && $config['attributes']['option'] && $this->tagName == 'option' && isset($this->children[0]) && $this->children[0]->value == $value) {
					unset($this->attributes[$key]);
				}

				// remove tag specific default attribute
				if (isset($config['attributes']['default'][$this->tagName][$key]) && ($config['attributes']['default'][$this->tagName][$key] === true || $config['attributes']['default'][$this->tagName][$key] == $value)) {
					unset($this->attributes[$key]);
				}

				// remove other attributes
				if (isset($config['attributes']['default'][''][$key]) && ($config['attributes']['default'][''][$key] === true || $config['attributes']['default'][''][$key] == $value)) {
					unset($this->attributes[$key]);
				}
			}

			// minify urls
			if ($config['urls'] && in_array($key, $config['urls']['attributes'])) {
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

				// strip scheme from absolute URL's if the same as current scheme
				if ($config['urls']['scheme']) {
					$prefix = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://';
					if (strpos($this->attributes[$key], $prefix) === 0) {
						$this->attributes[$key] = substr($this->attributes[$key], strlen($prefix)-2);
					}
				}
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

	public function compile(Array $config) {
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
			$html .= '</'.$this->tagName.'>';
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
