<?php
namespace hexydec\minify;
class htmlmin {

	protected static $minify = Array(
		'whitespace' => true, // strip whitespace from text nodes'
		'comments' => true, // remove comments
		'inlinestyles' => true, // minify inline CSS
		'urls' => true, // update internal URL's to be shorter
		'attributes' => true, // remove values from boolean attributes
	);
	protected static $config = Array(
		'preservewhitespace' => Array('script', 'style', 'textarea', 'pre', 'code'), // which elements not to strip whitespace from
		'cssmin' => false, //'\\hexydec\\minify\\cssmin::minify', // minify CSS
		'jsmin' => false, // minify javascript
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

	public static function minify($code, $minify = Array(), $config = Array()) {

		// special email setting
		if (!empty($minify['email'])) {
			self::$minify['urls'] = false;
		}
		$minify = array_merge(self::$minify, $minify);
		$config = array_merge(self::$config, $config);

		// strip HTML
		$replace = Array();

		// strip whitespace
		if ($minify['whitespace']) {
			$inline = implode('|', array_map('preg_quote', $config['whitespace']['inlineelements']));
			$replace = Array(
				'/\s++/' => function () {return ' ';}, // collapse whitespace to a single space character
				'/(?<!'.$inline.')> <(?!('.$inline.')[ >])/' => function () {return '><';}, // remove whitepace between elements except between inline elements
				'/(?<!'.$inline.')> <(?=('.$inline.')[ >])/' => function () {return '><';}, // remove whitepace between elements where an inline meets a block
				'/(?<='.$inline.')> <(?!('.$inline.')[ >])/' => function () {return '><';}, // remove whitepace between elements where an inline meets a block
				'/(?<!'.$inline.')> (?!<)/' => function () {return '>';}, // remove whitespace between opening tag and text, except where the tag is inline
				'/(?<!>) <(?!('.$inline.')[ >])/' => function () {return '<';}, // remove whitespace between text and closing tag, except where the tag is inline
				'/ ?\/>/' => function () {return '>';} // remove slash from self-closing tags
			);
		}

		// strip comments, allowing IE conditional tags if configured
		if ($minify['comments']) {
			$replace['/<!--'.($config['comments']['ie'] ? '(?!\[if [^\]]+\]|<!\[endif\])' : '').'.*-->/sU'] = function () {return '';};
		}

		// remove last semi-colon from style attributes
		if ($minify['inlinestyles']) {
			$replace['/ style="([^"]*)"/'] = function ($match) {
				return ' style="'.trim(str_replace(
					Array('  ', ' : ', ': ', ' :', ' ; ', ' ;', '; '),
					Array(' ', ':', ':', ':', ';', ';', ';'),
					$match[1]
				), '; ').'"';
			};
		}

		// make absolute URI's relative
		if ($minify['urls']) {
			$prefix = ' ('.implode('|', $config['urls']['attributes']).')="';
			if ($config['options']['urls']['absolute']) {
				$folder = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				if (substr($folder, -1) != '/') {
					$folder = dirname($folder).'/';
				}
				$replace['/'.$prefix.preg_quote($folder, '/').'([^"]++)"/i'] = function ($match) {return ' '.$match[1].'="'.$match[2].'"';};
			}

			// strip scheme from absolute URL's if the same as current scheme
			if ($config['options']['urls']['scheme']) {
				$replace['/'.$prefix.'http'.(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').':\/\//i'] = function ($match) {return ' '.$match[1].'="//';};
			}
		}

		// process HTML attributes
		if ($minify['attributes']) {

			// remove value attribute from <option> when same as text
			if ($config['attributes']['option']) {
				$replace['/(<option[^>]*) value="([^"]*+)"([^>]*>\\2<\/option>)/'] = function ($match) {return $match[1].$match[3];};
			}

			// remove type attribute from style|script
			if ($config['attributes']['type']) {
				$replace['/(<(?:script|style)[^>]*) type="text\/(?:css|javascript)"/'] = function ($match) {return $match[1];};
			}

			// remove method attribute from <form> tags
			if ($config['attributes']['method']) {
				$replace['/(<form[^>]*) method="get"/'] = function ($match) {return $match[1];};;
			}

			// collapse boolean attributes
			if ($config['attributes']['booleanattributes']) {
				$replace['/ ('.implode('|', $config['attributes']['booleanattributes']).')="[^"]*+"/'] = function ($match) {return ' '.$match[1];};
			}
		}

		// Split HTML
		$elems = implode('|', $config['preservewhitespace']);
		$arr = preg_split('/(<\/(?:'.$elems.')>|<(?:'.$elems.')[^>]*+>)/sU', $code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// build ending tags
		$end = Array();
		foreach ($config['preservewhitespace'] AS $tag) {
			$end[] = '</'.$tag.'>';
		}

		// set flags
		$min = true;
		$css = false;
		$js = false;

		// process each block
		foreach ($arr AS &$item) {

			// find closing tags, turn minifier on
			if (in_array($item, $end)) {
				$min = true;
				$css = false;
				$js = false;

			// process CSS
			} elseif ($css && $config['cssmin']) {
				$item = call_user_func($config['cssmin'], $item);

			// process javascript
			} elseif ($js && $config['jsmin']) {
				$item = call_user_func($config['jsmin'], $item);

			// make replacements in HTML
			} elseif ($min) {
				if ($replace) {
					$item = preg_replace_callback_array($replace, $item);
				}

				// check whether the next round should be minified
				foreach ($config['preservewhitespace'] AS $tag) {
					if (stripos($item, '<'.$tag) === 0) {
						$min = false;

						// turn javascript minifier on
						if ($tag == 'script') {
							$js = true;

						// turn CSS minifier on
						} elseif ($tag == 'style') {
							$css = true;
						}
						break;
					}
				}
			}
		}
		unset($item);
		return implode('', $arr);
	}

	/*public static function minifyDom($html, $config = Array()) {

		if (!empty($config['email'])) {
			self::$config['stripurls'] = true;
			self::$config['stripscheme'] = true;
		}
		$config = array_merge(self::$config, $config);

		libxml_use_internal_errors(true);
		$dom = new \DOMDocument();
		//$dom->substituteEntities = false;
		$dom->preserveWhiteSpace = false;
		$dom->strictErrorChecking = false;
		//$html = mb_convert_encoding($html, 'HTML-ENTITIES');
		$html = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html; charset='.strtolower(mb_internal_encoding()).'">', $html);
		if ($dom->loadHTML($html, LIBXML_COMPACT & LIBXML_HTML_NOIMPLIED)) {
			$xpath = new \DOMXPath($dom);

			if (($nodes = $xpath->query('//meta[@http-equiv="Content-Type"]')) !== false) {
				foreach ($nodes AS $item) {
					$item->parentNode->removeChild($item);
				}
			}

			// strip whitespace
			if ($config['stripwhitespace'] && ($nodes = $xpath->query('//*[not(self::script or self::style orself::textarea or self::pre)]/text()')) !== false) {
				foreach ($nodes AS $item) {

					// collapse whitespace
					$item->nodeValue = preg_replace('/\s++/', ' ', $item->nodeValue);

					// remove if only whitespace
					if ($item->nodeValue == ' ') {
						$item->parentNode->removeChild($item);
					}

					// left trim if no previous sibling
					if (!$item->previousSibling) {
						$item->nodeValue = ltrim($item->nodeValue);
					}

					// right trim if no next sibling
					if (!$item->nextSibling) {
						$item->nodeValue = rtrim($item->nodeValue);
					}
				}
			}

			// strip comments
			if ($config['stripcomments'] && ($nodes = $xpath->query('//comment()')) !== false) {
				foreach ($nodes AS $item) {
					if (strpos($item->nodeValue, '[if') !== 0) { // don't remove IE tags
						$item->parentNode->removeChild($item);
					}
				}
			}

			// strip URLs
			if ($config['stripurls']) {
				$folder = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
				if (substr($folder, -1) != '/') {
					$folder = dirname($folder).'/';
				}
				$len = strlen($folder);
				foreach (self::$config['urlattributes'] AS $name) {
					if (($nodes = $xpath->query('//*[starts-with(@'.$name.', "'.$folder.'")]')) !== false) {
						foreach ($nodes AS $item) {
							$item->setAttribute($name, substr($item->getAttribute($name), $len));
						}
					}
				}
			}

			// strip scheme
			if ($config['stripscheme']) {
				$scheme = $_SERVER['REQUEST_SCHEME'];
				$len = strlen($scheme) + 1;
				foreach (self::$config['urlattributes'] AS $name) {
					if (($nodes = $xpath->query('//*[starts-with(@'.$name.', "'.$scheme.'")]')) !== false) {
						foreach ($nodes AS $item) {
							$item->setAttribute($name, substr($item->getAttribute($name), $len));
						}
					}
				}
			}

			// remove value attribute where same as text
			if ($config['stripoption'] && ($nodes = $xpath->query('//option[@value=text()]')) !== false) {
				foreach ($nodes AS $item) {
					$item->removeAttribute('value');
				}
			}

			// remove type attribute from style|script
			if ($config['striptype']) {
				if (($nodes = $xpath->query('//script[@type="text/javascript"]')) !== false) {
					foreach ($nodes AS $item) {
						$item->removeAttribute('type');
					}
				}
				if (($nodes = $xpath->query('//style[@type="text/css"]')) !== false) {
					foreach ($nodes AS $item) {
						$item->removeAttribute('type');
					}
				}
			}

			// remove method attribute from form
			if ($config['stripmethod'] && ($nodes = $xpath->query('//form[@method="get"]')) !== false) {
				foreach ($nodes AS $item) {
					$item->removeAttribute('method');
				}
			}

			// remove last semi-colon from style attributes
			if ($config['stripinlinestyles'] && ($nodes = $xpath->query('//*[@style]')) !== false) {
				foreach ($nodes AS $item) {
					$item->setAttribute('style', trim(str_replace(' ', '', $item->getAttribute('style')), ';'));
				}
			}

			// minify css
			if ($config['cssmin'] && ($nodes = $xpath->query('//style')) !== false) {
				foreach ($nodes AS $item) {
					$item->nodeValue = call_user_func($config['cssmin'], $item->nodeValue);
				}
			}

			// minify javascript
			if ($config['jsmin'] && ($nodes = $xpath->query('//script')) !== false) {
				foreach ($nodes AS $item) {
					$item->nodeValue = trim(call_user_func($config['jsmin'], $item->nodeValue));
				}
			}

			// output document
			$html = $dom->saveHTML();
			//$html = mb_convert_encoding($html, mb_internal_encoding(), 'HTML-ENTITIES');

			// collapse attributes that have the same value
			if ($config['collapseattributes']) {
				$html = preg_replace('/ ('.implode('|', $config['booleanattributes']).')="(?:\\1|true)"/', ' $1', $html);
			}
		}
		return $html;
	}*/
}
