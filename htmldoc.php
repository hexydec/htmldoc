<?php
namespace hexydec\minify;
class htmldoc {

	protected $minify = Array(
		'whitespace' => true, // strip whitespace from text nodes'
		'comments' => true, // remove comments
		'urls' => true, // update internal URL's to be shorter
		'attributes' => true, // remove values from boolean attributes
		'lowercase' => true // lowercase tag and attribute names
	);
	protected $config = Array(
		'tokens' => Array(
			'doctype' => '<!DOCTYPE',
			'comment' => '<!--[\d\D]*?-->',
			'tagopenstart' => '<[^ >\/]++',
			'tagselfclose' => '\/>',
			'tagopenend' => '>',
			'tagclose' => '<\/[^ >]++>',
			'textnode' => '(?<=>)[^<]++(?=<)',
			'attributevalue' => '=\s*+["\']?[^"\']*+["\']?',
			'attribute' => '[^<>"=\s]++',
			'whitespace' => '\s++'
		),
		'preservewhitespace' => Array('script', 'style', 'textarea', 'pre', 'code'), // which elements not to strip whitespace from
		'cssmin' => '\\hexydec\\minify\\cssmin::minify', // minify CSS
		'jsmin' => false, // minify javascript
		'lowercase' => true, // lowercase tag and attribute names
		'whitespace' => Array(
			'inlineelements' => Array(
				'b',
				'big',
				'i',
				'small',
				'ttspan',
				'em',
				'a',
				'strong',
				'sub',
				'sup',
				'abbr',
				'acronym',
				'cite',
				'code',
				'dfn',
				'em',
				'kbd',
				'strong',
				'samp',
				'var'
			)
		),
		'comments' => Array(
			'ie' => true
		),
		'urls' => Array(
			'attributes' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
			'absolute' => true, // process absolute URLs to make them relative to the current document
			'scheme' => true // remove the scheme from URLs that have the same scheme as the current document
		),
		'attributes' => Array(
			'option' => true, // remove value attribute from option where the text node has the same value
			'type' => true, // remove the type attribute from script and style tags
			'method' => true, // remove method from form tags where it is set to GET
			'style' => true, // minify the style tag
			'booleanattributes' => Array(
				'allowfullscreen',
				'allowpaymentrequest',
				'async',
				'autofocus',
				'autoplay',
				'checked',
				'contenteditable',
				'controls',
				'default',
				'defer',
				'disabled',
				'formnovalidate',
				'hidden',
				'indeterminate',
				'ismap',
				'itemscope',
				'loop',
				'multiple',
				'muted',
				'nomodule',
				'novalidate',
				'open',
				'readonly',
				'required',
				'reversed',
				'scoped',
				'selected',
				'typemustmatch'
			)
		)
	);
	protected $document = false;

	public function __construct(Array $config = Array()) {
		$this->config = array_replace_recursive($this->config, $config);
	}

	public function open(String $url) {
		if (($html = file_get_contents($url)) !== false) {
			return $this->load($html);
		}
		return false;
	}

	public function load(String $html) {
		if (($tokens = tokenise::tokenise($html, $this->config)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);
		} elseif (($this->document = $this->parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}

	protected function parse(Array $tokens, int $count = null, int &$i = 0) : Array {
		// var_dump($tokens);
		if ($count === null) {
			$count = count($tokens);
		}
		$tag = false;
		$ast = Array();
		while ($i < $count) {
			switch ($tokens[$i]['type']) {
				case 'doctype':
					$item = $this->parseDoctype($tokens, $count, $i);
					if (empty($ast)) { // only add if found at the top of the document
						$ast[] = $item;
					}
					break;

				case 'tagopenstart':
					$tag = trim($tokens[$i]['value'], '<');
					$ast[] = $this->parseTag($tokens, $count, $i);
					break;

				case 'tagclose':
					if (strtolower(trim($tokens[$i]['value'], '</>')) != strtolower($tag)) {
						break 2;
					}
					break;

				case 'textnode':
					$ast[] = Array(
						'type' => 'text',
						'value' => html_entity_decode($tokens[$i]['value'], ENT_QUOTES)
					);
					break;

				case 'comment':
					$ast[] = Array(
						'type' => 'comment',
						'value' => $tokens[$i]['value']
					);
					break;
			}
			$i++;
		}
		return $ast;
	}

	protected function parseDoctype(Array $tokens, int $count, int &$i) : Array {
		$value = '';
		while (++$i < $count && $tokens[$i]['type'] != 'tagopenend') {
			if ($tokens[$i]['type'] == 'attribute') {
				$value .= ($value ? ' ' : '').$tokens[$i]['value'];
			}
		}
		return Array(
			'type' => 'doctype',
			'value' => $value
		);
	}

	protected function parseTag(Array $tokens, int $count, int &$i) : Array {
		if ($tokens[$i]['type'] == 'tagopenstart') {
			$item = Array(
				'type' => 'tag',
				'tag' => trim($tokens[$i]['value'], '<'),
				'attributes' => Array(),
				'selfclose' => false
			);
			$attr = false;
			while (++$i < $count) {
				switch ($tokens[$i]['type']) {

					// remember attribute
					case 'attribute':
						if ($attr) {
							$item['attributes'][$attr] = null;
							$attr = false;
						}
						$attr = $tokens[$i]['value'];
						break;

					// record attribute and value
					case 'attributevalue':
						if ($attr) {
							$item['attributes'][$attr] = html_entity_decode(trim($tokens[$i]['value'], '= "'), ENT_QUOTES); // set charset?
							$attr = false;
						}
						break;

					case 'tagopenend':

						// don't process certain tags
						if (in_array($item['tag'], $this->config['preservewhitespace'])) {
							$item['content'] = '';
							while (++$i < $count && ($tokens[$i]['type'] != 'tagclose' || $tokens[$i]['value'] != '</'.$item['tag'].'>')) {
								$item['content'] .= $tokens[$i]['value'];
							}

						// parse children
						} else {
							$i++;
							$item['children'] = $this->parse($tokens, $count, $i);
						}
						break 2;

					case 'tagselfclose':
						$item['selfclose'] = true;
						break 2;
				}
			}
			if ($attr) {
				$item['attributes'][$attr] = null;
				$attr = false;
			}
			return $item;
		}
	}

	public function minify(Array $config = Array()) {
		$config = array_replace_recursive($this->config, $config);
		$this->document = $this->minifyAst($this->document, $config);
	}

	protected function minifyAst(Array $ast, Array $config, String &$parentTag = null) : Array {
		$len = count($ast);
		$folder = false;
		$lasttag = false;
		for ($i = 0; $i < $len; $i++) {
			switch ($ast[$i]['type']) {
				case 'tag':
					if ($config['lowercase']) {
						$ast[$i]['tag'] = strtolower($ast[$i]['tag']);
					}
					$lasttag = $ast[$i]['tag'];

					// minify attributes
					$attributes = Array();
					foreach ($ast[$i]['attributes'] AS $key => $value) {

						// lowercase attribute key
						if ($config['lowercase']) {
							unset($ast[$i]['attributes'][$key]);
							$key = strtolower($key);
							$ast[$i]['attributes'][$key] = $value;
						}

						// minify attributes
						if ($config['attributes']) {

							// boolean attributes
							if (in_array($key, $config['attributes']['booleanattributes'])) {
								$ast[$i]['attributes'][$key] = null;
							}

							// minify style tag
							if ($key == 'style' && $config['attributes']['style']) {
								$ast[$i]['attributes'][$key] = trim(str_replace(
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
								if (strpos($ast[$i]['attributes'][$key], $folder) === 0) {
									$ast[$i]['attributes'][$key] = substr($ast[$i]['attributes'][$key], strlen($folder));
								}
							}

							// strip scheme from absolute URL's if the same as current scheme
							if ($config['urls']['scheme']) {
								$prefix = 'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://';
								if (strpos($ast[$i]['attributes'][$key], $prefix) === 0) {
									$ast[$i]['attributes'][$key] = substr($ast[$i]['attributes'][$key], strlen($prefix)-2);
								}
							}
						}
					}

					if ($config['attributes']) {

						// minify option tag
						if ($ast[$i]['tag'] == 'option' && $config['attributes']['option'] && isset($ast[$i]['attributes']['value'], $ast[$i]['children'][0]) && $ast[$i]['children'][0]['value'] == $ast[$i]['attributes']['value']) {
							unset($ast[$i]['attributes']['value']);
						}

						// minify type tag
						if (in_array($ast[$i]['tag'], Array('style', 'script')) && $config['attributes']['type']) {
							unset($ast[$i]['attributes']['type']);
						}

						// minify method tag
						if ($ast[$i]['tag'] == 'form' && $config['attributes']['method'] && isset($ast[$i]['attributes']['method']) && $ast[$i]['attributes']['method'] == 'get') {
							unset($ast[$i]['attributes']['method']);
						}
					}

					// minify children
					if (!empty($ast[$i]['children'])) {
						$ast[$i]['children'] = $this->minifyAst($ast[$i]['children'], $config, $lasttag);

					// minify content
					} elseif (!empty($ast[$i]['content'])) {

						// minify CSS
						if ($config['cssmin'] && $ast[$i]['tag'] == 'style') {
							$ast[$i]['content'] = call_user_func($config['cssmin'], $ast[$i]['content']);

						// minify CSS
						} elseif ($config['jsmin'] && $ast[$i]['tag'] == 'script') {
							$ast[$i]['content'] = call_user_func($config['jsmin'], $ast[$i]['content']);
						}
					}
					break;

				case 'text':

					// collapse whitespace
					if ($config['whitespace']) {
						$ast[$i]['value'] = preg_replace('/\s++/', ' ', $ast[$i]['value']);

						// if last tag is a block element, ltrim the textnode
						if (!in_array($lasttag ? $lasttag : $parentTag, $config['whitespace']['inlineelements'])) {
							$ast[$i]['value'] = ltrim($ast[$i]['value']);
						}

						// if next tag is a block element, rtrim the textnode
						$tag = isset($ast[$i + 1]['tag']) ? $ast[$i + 1]['tag'] : $parentTag; // if last element use parent
						if (!in_array($tag, $config['whitespace']['inlineelements'])) {
							$ast[$i]['value'] = rtrim($ast[$i]['value']);
						}

						// if the textnode is empty, remove it
						if ($ast[$i]['value'] === '') {
							unset($ast[$i]);
						}
					}
					break;

				case 'comment':
					if ($config['comments'] && (empty($config['comments']['ie']) || (strpos($ast[$i]['value'], '<!--[if ') !== 0 && $ast[$i]['value'] != '<!--<![endif]-->'))) {
						unset($ast[$i]);
					}
					break;
			}
		}
		return $ast;
	}

	public function save(string $file = null) {
		$html = $this->compile($this->document);
		if (!$file) {
			return $html;
		} elseif (file_put_contents($file, $html) === false) {
			trigger_error('File could not be written', E_USER_WARNING);
		} else {
			return true;
		}
		return false;
	}

	protected function compile(Array $ast) {
		$html = '';
		foreach ($ast AS $item) {
			switch ($item['type']) {

				case 'doctype':
					$html .= '<!DOCTYPE '.$item['value'].'>';
					break;

				case 'tag':
					$html .= '<'.$item['tag'];
					foreach ($item['attributes'] AS $key => $value) {
						$html .= ' '.$key;
						if ($value !== null) {
							$html .= '="'.htmlspecialchars($value).'"';
						}
					}
					if ($item['selfclose']) {
						$html .= '/>';
					} else {
						$html .= '>';
						if (!empty($item['children'])) {
							$html .= $this->compile($item['children']);
						} elseif (!empty($item['content'])) {
							$html .= $item['content'];
						}
						$html .= '</'.$item['tag'].'>';
					}
					break;

				case 'text':
					$html .= htmlspecialchars($item['value']);
					break;

				case 'comment':
					$html .= $item['value'];
					break;
			}
		}
		return $html;
	}
}
