<?php
namespace hexydec\html;

class tag {

	protected $config = Array();
	protected $tag;
	protected $attributes = Array();
	protected $children = Array();
	protected $singleton = false;
	protected $quotes = 'double';

	public function __construct($tag, $config) {
		$this->tag = $tag;
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

					// don't process certain tags
					if (in_array($this->tag, $this->config['elements']['preserve'])) {
						$item['content'] = '';
						while (($token = next($tokens)) !== false && ($token['type'] != 'tagclose' || $token['value'] != '</'.$this->tag.'>')) {
							$item['content'] .= $token['value'];
						}

					// parse children
					} elseif (!in_array($this->tag, $this->config['elements']['singleton'])) {
						next($tokens);
						$ast = new ast($this->config);
						$this->children = $ast->parse($tokens, $this->tag, $attach);
					}
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

	public function minify(Array $config, String &$parentTag = null) {
		if ($config['lowercase']) {
			$this->tag = strtolower($this->tag);
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

				// boolean attributes
				if ($config['attributes']['boolean']) {
					if (in_array($key, $this->config['elements']['booleanattributes'])) {
						$this->attributes[$key] = null;
					}
				}

				// minify style tag
				if ($key == 'style' && $config['attributes']['style']) {
					$this->attributes[$key] = trim(str_replace(
						Array('  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '),
						Array(' ', ':', ':', ':', ';', ';', ';'),
						$value
					), '; ');
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

		if ($config['attributes']) {

			// minify option tag
			if ($this->tag == 'option' && $config['attributes']['option'] && isset($this->attributes['value'], $this->children[0]) && $this->children[0]->value == $this->attributes['value']) {
				unset($this->attributes['value']);
			}

			// minify type tag
			if (in_array($this->tag, Array('style', 'script')) && $config['attributes']['type']) {
				unset($this->attributes['type']);
			}

			// minify method tag
			if ($this->tag == 'form' && $config['attributes']['method'] && isset($this->attributes['method']) && $this->attributes['method'] == 'get') {
				unset($this->attributes['method']);
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
		if (!empty($this->children)) {
			foreach ($this->children AS $item) {
				$item->minify($config, $tag);
			}

		// minify content
		// } elseif (!empty($ast[$i]['content'])) {
		//
		// 	// minify CSS
		// 	if ($config['cssmin'] && $this->tag == 'style') {
		// 		$ast[$i]['content'] = call_user_func($config['cssmin'], $ast[$i]['content']);
		//
		// 	// minify CSS
		// 	} elseif ($config['jsmin'] && $this->tag == 'script') {
		// 		$ast[$i]['content'] = call_user_func($config['jsmin'], $ast[$i]['content']);
		// 	}
		}
	}

	public function compile() {
		$html = '<'.$this->tag;

		// compile attributes
		foreach ($this->attributes AS $key => $value) {
			$html .= ' '.$key;
			if ($value !== null) {
				$quote = '"';
				if ($this->config['output']['quotestyle'] == 'single') {
					$quote = "'";
				} elseif ($value && $this->config['output']['quotestyle'] == 'minimal' && strcspn($value, " =\"'`<>\n\r\t") == strlen($value)) {
					$quote = '';
				}
				$html .= '='.$quote.htmlspecialchars($value).$quote;
			}
		}

		// close singleton tags
		if (in_array($this->tag, $this->config['elements']['singleton'])) {
			$html .= $this->config['output']['singletonclose'];

		// close opening tag and compile contents
		} else {
			$html .= '>';
			if (!empty($this->children)) {
				foreach ($this->children AS $item) {
					$html .= $item->compile();
				}
			} elseif (!empty($item['content'])) {
				$html .= $item['content'];
			}
			$html .= '</'.$this->tag.'>';
		}
		return $html;
	}
}
