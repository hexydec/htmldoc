<?php
namespace hexydec\html;
class htmldoc {

	protected $config = Array(
		'tokens' => Array(
			'doctype' => '<!DOCTYPE',
			'comment' => '<!--[\d\D]*?-->',
			'cdata' => '<!\[CDATA\[[\d\D]*?\]\]>',
			'tagopenstart' => '<[^ >\/]++',
			'tagselfclose' => '\/>',
			'tagopenend' => '>',
			'tagclose' => '<\/[^ >]++>',
			'textnode' => '(?<=>)[^<]++(?=<)',
			'attributevalue' => '=\s*+["\']?[^"\']*+["\']?',
			'attribute' => '[^<>"=\s]++',
			'whitespace' => '\s++'
		),
		'elements' => Array(
			'inline' => Array(
				'b', 'big', 'i', 'small', 'ttspan', 'em', 'a', 'strong', 'sub', 'sup', 'abbr', 'acronym', 'cite', 'code', 'dfn', 'em', 'kbd', 'strong', 'samp', 'var'
			),
			'singleton' => Array(
				'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
			),
			'unnestable' => Array(
				'p', 'dt', 'dd', 'li', 'option', 'thead', 'th', 'tbody', 'tr', 'td', 'tfoot', 'colgroup', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'
			),
			'preserve' => Array('script', 'style', 'textarea', 'pre', 'code'), // which elements not to strip whitespace from
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
		),
		'minify' => Array(
			'cssmin' => '\\hexydec\\minify\\cssmin::minify', // minify CSS
			'jsmin' => false, // minify javascript
			'lowercase' => true, // lowercase tag and attribute names
			'whitespace' => true, // strip whitespace from text nodes
			'comments' => Array( // remove comments
				'ie' => true
			),
			'urls' => Array( // update internal URL's to be shorter
				'attributes' => Array('href', 'src', 'action', 'poster'), // attributes to minify URLs in
				'absolute' => true, // process absolute URLs to make them relative to the current document
				'scheme' => true // remove the scheme from URLs that have the same scheme as the current document
			),
			'attributes' => Array( // remove values from boolean attributes
				'option' => true, // remove value attribute from option where the text node has the same value
				'type' => true, // remove the type attribute from script and style tags
				'method' => true, // remove method from form tags where it is set to GET
				'style' => true, // minify the style tag
				'removequotes' => true, // remove quotes from attributes where possible
				'sort' => true, // sort attributes for better gzip
				'boolean' => true // minify boolean attributes
			),
			'singleton' => true, // minify singleton element by removing slash
			'quotes' => true, // minify attribute quotes
		),
		'output' => Array(
			'charset' => 'utf-8',
			'quotestyle' => 'double', // double, single, minimal
			'singletonclose' => ' />',

		)
	);
	protected $document = false;
	protected $attributes = Array();

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
		$this->attributes = Array();
		if (($tokens = $this->tokenise($html, $this->config)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);
		} elseif (($this->document = $this->parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);
		} else {
			// print_r($this->document);
			return true;
		}
		return false;
	}

	protected function tokenise($code, $config) {

		// prepare regexp and extract strings
		$re = '/('.implode(')|(', $config['tokens']).')/';
		if (preg_match_all($re, $code, $match)) {

			// build tokens into types
			$tokens = Array();
			$keys = array_keys($config['tokens']);
			foreach ($match[0] AS $i => $item) {

				// go through tokens and find which one matched
				foreach ($keys AS $token => $type) {
					if ($match[$token+1][$i] !== '') {
						$tokens[] = Array(
							'type' => $type,
							'value' => $item
						);
						break;
					}
				}
			}
			return $tokens;
		}
		return false;
	}

	protected function parse(Array $tokens, int $count = null, int &$i = 0, string $parenttag = null, array &$attach = null) : Array {
		static $level = 0;
		$level++;

		// if no count supplied, count the tokens
		if ($count === null) {
			$count = count($tokens);
		}

		// build AST
		$tag = null;
		$ast = Array();
		$start = false;
		while ($i < $count) {
			switch ($tokens[$i]['type']) {
				case 'doctype':
					$item = $this->parseDoctype($tokens, $count, $i);
					if (empty($ast)) { // only add if found at the top of the document
						$ast[] = $item;
					}
					break;

				case 'tagopenstart':
					$tag = strtolower(trim($tokens[$i]['value'], '<'));

					// parse the tag
					$ast[] = $this->parseTag($tag, $tokens, $count, $i, $attach);
					if ($attach) {
						$ast[] = $attach;
						$attach = null;
					}
					break;

				case 'tagclose':
					$close = strtolower(trim($tokens[$i]['value'], '</>'));
					if ($close != $tag) { // if tags not the same, go back to previous level

						// if a tag isn't closed and we are closing a tag that isn't the parent, send the last child tag to the parent level
						if ($tag && $parenttag != $close && end($ast)['type'] == 'tag') {
							$attach = array_pop($ast);
						}
						$i--; // close the tag on each level below until we find itself
						break 2;
					}
					break;

				case 'textnode':
					$ast[] = Array(
						'type' => 'text',
						'value' => html_entity_decode($tokens[$i]['value'], ENT_QUOTES)
					);
					break;

				case 'cdata':
					$ast[] = Array(
						'type' => 'cdata',
						'value' => substr($tokens[$i]['value'], 9, -3)
					);
					break;

				case 'comment':
					$ast[] = Array(
						'type' => 'comment',
						'value' => substr($tokens[$i]['value'], 4, -3)
					);
					break;
			}
			$i++;
		}
		$level--;
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

	protected function parseTag(string $tag, Array $tokens, int $count, int &$i, array &$attach = null) : Array {
		$item = Array(
			'type' => 'tag',
			'tag' => $tag,
			'attributes' => Array()
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

					// cache attribute for minifier
					$this->attributes[$attr] = isset($this->attributes[$attr]) ? $this->attributes[$attr] + 1 : 1;
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
					if (in_array($tag, $this->config['elements']['preserve'])) {
						$item['content'] = '';
						while (++$i < $count && ($tokens[$i]['type'] != 'tagclose' || $tokens[$i]['value'] != '</'.$tag.'>')) {
							$item['content'] .= $tokens[$i]['value'];
						}

					// parse children
					} elseif (!in_array($tag, $this->config['elements']['singleton'])) {
						$i++;
						$item['children'] = $this->parse($tokens, $count, $i, $tag, $attach);
					}
					break 2;

				case 'tagselfclose':
					break 2;
			}
		}
		if ($attr) {
			$item['attributes'][$attr] = null;
			$attr = false;
		}
		return $item;
	}

	public function minify(Array $config = Array()) {

		// merge config
		$config = array_replace_recursive($this->config['minify'], $config);

		// set minify output parameters
		if ($config['singleton']) {
			$this->config['output']['singletonclose'] = '>';
		}
		if ($config['quotes']) {
			$this->config['output']['quotestyle'] = 'minimal';
		}

		// sort attributes
		if ($config['attributes']['sort']) {
			arsort($this->attributes, SORT_NUMERIC);
			$config['attributes']['sort'] = \array_keys($this->attributes);
		}
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
							if ($config['attributes']['boolean']) {
								if (in_array($key, $this->config['elements']['booleanattributes'])) {
									$ast[$i]['attributes'][$key] = null;
								}
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

					// sort attributes
					if ($config['attributes']['sort']) {
						$attr = $ast[$i]['attributes'];
						$ast[$i]['attributes'] = Array();
						foreach ($config['attributes']['sort'] AS $key) {
							if (isset($attr[$key])) {
								$ast[$i]['attributes'][$key] = $attr[$key];
							}
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
						if (!in_array($lasttag ? $lasttag : $parentTag, $this->config['elements']['inline'])) {
							$ast[$i]['value'] = ltrim($ast[$i]['value']);
						}

						// if next tag is a block element, rtrim the textnode
						$tag = isset($ast[$i + 1]['tag']) ? $ast[$i + 1]['tag'] : $parentTag; // if last element use parent
						if (!in_array($tag, $this->config['elements']['inline'])) {
							$ast[$i]['value'] = rtrim($ast[$i]['value']);
						}

						// if the textnode is empty, remove it
						if ($ast[$i]['value'] === '') {
							unset($ast[$i]);
						}
					}
					break;

				case 'comment':
					if ($config['comments'] && (empty($config['comments']['ie']) || (strpos($ast[$i]['value'], '[if ') !== 0 && $ast[$i]['value'] != '<![endif]'))) {
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
		$output = $this->config['output'];
		$singleton = $this->config['elements']['singleton'];
		$html = '';
		foreach ($ast AS $item) {
			switch ($item['type']) {

				case 'doctype':
					$html .= '<!DOCTYPE '.$item['value'].'>';
					break;

				// build tag
				case 'tag':
					$html .= '<'.$item['tag'];

					// compile attributes
					foreach ($item['attributes'] AS $key => $value) {
						$html .= ' '.$key;
						if ($value !== null) {
							$quote = '"';
							if ($output['quotestyle'] == 'single') {
								$quote = "'";
							} elseif ($value && $output['quotestyle'] == 'minimal' && strcspn($value, " =\"'`<>\n\r\t") == strlen($value)) {
								$quote = '';
							}
							$html .= '='.$quote.htmlspecialchars($value).$quote;
						}
					}

					// close singleton tags
					if (in_array($item['tag'], $singleton)) {
						$html .= $output['singletonclose'];

					// close opening tag and compile contents
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
					$html .= '<!--'.$item['value'].'-->';
					break;

				case 'cdata':
					$html .= '<[CDATA['.$item['value'].']]>';
					break;
			}
		}
		return $html;
	}
}
