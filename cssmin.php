<?php
namespace hexydec\html;
class cssmin {

	protected static $config = Array(
		'removesemicolon' => true,
		'removezerounits' => true,
		'removeleadingzero' => true,
		'convertquotes' => true,
		'removequotes' => true,
		'shortenhex' => true,
		'lowerhex' => true,
		'sortselectors' => true,
		'mergeselectors' => true,
		'removeoverwrittenproperties' => true,
		'sortproperties' => true,
		'mergeblocks' => true,
		'output' => 'minify',
		'report' => false,
		'tokens' => Array(
			'whitespace' => '\s++',
			'comment' => '\/\*(?!!)[\d\D]*?\*\/',
			'quotes' => '(?<!\\\\)"(?:[^"\\\\]++|\\\\.)*+"',
			'join' => '[>+~]',
			'comparison' => '[\^*$<>]?=', // comparison operators for media queries or attribute selectors
			'curlyopen' => '{',
			'curlyclose' => '}',
			'squareopen' => '\[',
			'squareclose' => '\]',
			'bracketopen' => '\(',
			'bracketclose' => '\)',
			'comma' => ',',
			'colon' => ':',
			'semicolon' => ';',
			'string' => '!?[^\[\]{}\(\):;,>+=~\^$!" ]++'
		)
	);

	public static function minify($code, $config = Array()) {
		$config = array_merge(self::$config, $config);
		if (($tokens = tokenise::tokenise($code, $config)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);
		} elseif (($ast = self::parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);
		} elseif (($ast = self::minifyAst($ast)) === false) {
			trigger_error('AST could not be minified', E_USER_WARNING);
		} elseif (($css = self::compile($ast, $config)) === false) {
			trigger_error('Could not compile CSS', E_USER_WARNING);
		} else {
			return $css;
		}


		// $code = self::clean($code);
		// if (($rules = self::tokenise($code, $config)) !== false) {
		// 	return self::compile($rules, $config['output'] == 'minify', !$config['removesemicolon']);
		// }
		// return false;
	}

	protected function tokenise($code, $config) {

		// prepare regexp and extract strings
		$re = '/('.implode(')|(', $config['tokens']).')/u';
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

	protected static function parse($tokens, &$i = 0) {
		$rules = Array();
		$count = count($tokens);
		$select = true;
		$comment = false;
		$media = false;
		$selectors = Array();
		$properties = Array();
		while ($i < $count) {

			// process selectors
			if ($select) {
				if ($tokens[$i]['type'] != 'whitespace') {
					if ($tokens[$i]['type'] == 'comment') {
						$comment = $tokens[$i]['value'];
					} elseif ($tokens[$i]['type'] == 'curlyclose') {
						return $rules;
					} elseif ($tokens[$i]['type'] == 'string') {
						if ($tokens[$i]['value'] == '@media') {
							$i++;
							$rules[] = Array(
								'media' => self::parseMediaQuery($tokens, $count, $i),
								'rules' => self::parse($tokens, $i),
								'comment' => $comment
							);
							$comment = false;
						} else {
							$i--;
							$selectors[] = self::parseSelectors($tokens, $count, $i);
							if ($tokens[$i]['type'] == 'curlyopen') {
								$select = false;
							}
						}
					}
				}

			// process properties
			} elseif ($tokens[$i]['type'] == 'string') {
				$prop = $tokens[$i]['value'];
				if ($tokens[++$i]['type'] == 'colon') {
					$properties[$prop] = Array(
						'value' => self::parsePropertyValue($tokens, $count, $i, $important, $propcomment),
						'important' => $important,
						'semicolon' => ';',
						'comment' => $propcomment
					);
				}

			// end rule
			} elseif ($tokens[$i]['type'] == 'curlyclose') {
				$rules[] = Array(
					'selectors' => $selectors,
					'properties' => $properties,
					'comment' => $comment
				);
				$selectors = Array();
				$properties = Array();
				$select = true;
				$comment = false;
			}
			$i++;
		}
		// print_r($rules);
		// exit();
		return $rules;
	}

	protected static function parseMediaQuery($tokens, $count, &$i) {
		$media = Array();
		$default = $rule = Array(
			'media' => 'all',
			'only' => false,
			'not' => false,
			'properties' => Array()
		);
		while ($i++ < $count) {
			switch ($tokens[$i]['type']) {
				case 'string':
					if ($tokens[$i]['value'] == 'only') {
						$rule['only'] = true;
					} elseif ($tokens[$i]['value'] == 'not') {
						$rule['not'] = true;
					} elseif ($tokens[$i]['value'] != 'and') {
						$rule['media'] = $tokens[$i]['value'];
					}
					break;
				case 'bracketopen':
					$compare = false;
					while ($i++ < $count && $tokens[$i]['type'] != 'bracketclose') {
						if ($tokens[$i]['type'] == 'string') {
							if (!$compare) {
								$prop = $tokens[$i]['value'];
							} elseif ($compare == ':') {
								$rule['properties'][$prop] = $tokens[$i]['value'];
								$compare = false;
							} else {
								if (intval($prop)) {
									$rule['properties']['min-'.$tokens[$i]['value']] = $prop;
									$prop = 'max'.$tokens[$i]['value'];
								} else {
									$rule['properties'][$prop] = $tokens[$i]['value'];
								}
							}
						} elseif ($tokens[$i]['type'] == 'colon') {
							$compare = ':';
						} elseif ($tokens[$i]['type'] == 'comparison' && $tokens[$i]['value'] == '<=') {
							$compare = '<=';
						}
					}
					break;
				case 'comma':
					$media[] = $rule;
					$rule = $default;
					break;
				case 'curlyopen':
					break 2;
			}
		}
		$media[] = $rule;
		return $media;
	}

	protected static function parseSelectors($tokens, $count, &$i) {
		$selector = Array();
		$join = false;
		while ($i++ < $count) {
			switch ($tokens[$i]['type']) {
				case 'whitespace':
					if (!$join) {
						$join = ' ';
					}
					break;
				case 'string':
					$selector[] = Array(
						'selector' => $tokens[$i]['value'],
						'join' => $join
					);
					$join = false;
					break;
				case 'colon':
					$parts = ':';
					while ($i++ < $count) {
						if (!in_array($tokens[$i]['type'], Array('whitespace', 'comma', 'curlyopen'))) {
							$parts .= $tokens[$i]['value'];
						} else {
							$i--;
							break;
						}
					}
					$selector[] = Array(
						'selector' => $parts,
						'join' => $join
					);
					$join = false;
					break;
				case 'squareopen':
					$parts = '';
					while ($i++ < $count) {
						if (!in_array($tokens[$i]['type'], Array('squareclose'))) {
							$parts .= $tokens[$i]['value'];
						} elseif ($tokens[$i]['type'] != 'whitespace') {
							$i--;
							break;
						}
					}
					$selector[] = Array(
						'selector' => '['.$parts.']',
						'join' => $join
					);
					$join = false;
					break;
				case 'curlyopen':
				case 'comma':
					break 2;
				case 'join':
					$join = $tokens[$i]['value'];
					break;
			}
		}
		return $selector;
	}

	protected static function parsePropertyValue($tokens, $count, &$i, &$important = false, &$comment = false) {
		$properties = Array();
		$values = Array();
		$important = false;
		$comment = false;
		while ($i++ < $count) {
			if ($tokens[$i]['type'] == 'comma') {
				$properties[] = $values;
				$values = Array();
			} elseif ($tokens[$i]['value'] == '!important') {
				$important = true;
			} elseif ($tokens[$i]['type'] == 'bracketopen') {
				$values[] = self::parsePropertyValue($tokens, $count, $i);
			} elseif (in_array($tokens[$i]['type'], Array('semicolon', 'bracketclose'))) {
				$n = $i;
				while ($n++ < $count) {
					if ($tokens[$n]['type'] == 'comment') {
						$comment = $tokens[$n]['value'];
					} elseif ($tokens[$n]['type'] != 'whitespace') {
						break;
					}
				}
				break;
			} elseif ($tokens[$i]['type'] != 'whitespace') {
				$values[] = $tokens[$i]['value'];
			}
		}
		if ($values) {
			$properties[] = $values;
		}
		return $properties;
	}

	protected static function minifyAst($ast) {
		foreach ($ast AS &$item) {
			if (isset($item['media'])) {
				$item['rules'] = self::minifyAst($item['rules']);
			} else {
				foreach ($item['properties'] AS &$prop) {
					foreach ($prop['value'] AS &$group) {
						$group = self::minifyValues($group);
					}
					unset($group);
				}
				unset($prop);
			}
		}
		unset($item);
		return $ast;
	}

	protected static function minifyValues($values) {
		$config = self::$config;
		foreach ($values AS &$value) {

			// value in brackets
			if (is_array($value)) {
				$value = self::minifyValues($value);
			} else {
				if ($config['removezerounits'] && preg_match('/^0(?:\.0*)?[a-z%]++$/i', $value)) {
					$value = '0';
				}
				if ($config['removeleadingzero'] && preg_match('/^0++(\.0*+[1-9][0-9%a-z]*+)$/', $value, $match)) {
					$value = $match[1];
				}
				if ($config['removequotes'] && preg_match('/^("|\')([^ \'"]++)\\2$/i', $value, $match)) {
					$value = $match[2];
				} elseif ($config['convertquotes']) {

				}
				if ($config['shortenhex'] && preg_match('/^#(([a-f0-6])\\2)(([a-f0-6])\\4)(([a-f0-6])\\6)/i', $value, $match)) {
					$value = '#'.$match[2].$match[4].$match[6];
				}
				if ($config['lowerhex'] && preg_match('/^#[a-f0-6]{3,6}$/i', $value)) {
					$value = strtolower($value);
				}
				if ($config['sortselectors']) {

				}
				if ($config['mergeselectors']) {

				}
				if ($config['removeoverwrittenproperties']) {

				}
				if ($config['sortproperties']) {

				}
				if ($config['mergeblocks']) {

				}
				if ($config['removesemicolon']) {

				}
			}
		}
		unset($value);
		return $values;
	}

	protected static function compile($ast, $config, $indent = 0) {
		$b = $config['output'] != 'minify';
		$tabs = $b ? str_repeat("\t", $indent) : '';
		$css = '';
		foreach ($ast AS $item) {

			// comment
			if ($b && $item['comment']) {
				$css .= $tabs.$item['comment']."\n";
			}

			// build properties
			if (isset($item['media'])) {
				$css .= '@media';
				foreach ($item['media'] AS $i => $media) {
					if ($media['only']) {
						$css .= ' only';
					}
					if ($media['media']) {
						$css .= ' '.$media['media'];
					}
					if ($media['not']) {
						$css .= ' not';
					}
					$join = $b ? ' ' : '';
					foreach ($media['properties'] AS $prop => $value) {
						$css .= $join.'('.$prop.':'.($b ? ' ' : '').$value.')';
						$join = ' and ';
					}
				}
				$css .= $b ? " {\n" : '{';
				$css .= self::compile($item['rules'], $config, $indent + 1);
			} else {

				// build selectors
				foreach ($item['selectors'] AS $i => $selector) {
					if ($i) {
						$css .= $b ? ', ' : ',';
					} else {
						$css .= $tabs;
					}
					foreach ($selector AS $select) {
						if (!empty($select['join'])) {
							$css .= $b && $select['join'] != ' ' ? ' '.$select['join'].' ' : $select['join'];
						}
						$css .= $select['selector'];
					}
				}
				$css .= $b ? ' {' : '{';

				foreach ($item['properties'] AS $prop => $value) {
					$css .= ($b ? "\n\t".$tabs : '').$prop.($b ? ': ' : ':');
					$css .= self::compileProperty($value['value'], $b);
					if ($value['important']) {
						$css .= ($b ? ' ' : '').'!important';
					}
					$css .= $value['semicolon'];
					if ($b && $value['comment']) {
						$css .= ' '.$value['comment'];
					}
				}
			}

			$css .= $b ? "\n".$tabs."}\n\n" : '}';
		}
		return rtrim($css);
	}

	protected static function compileProperty($value, $b) {
		$properties = Array();
		foreach ($value AS $group) {
			$compiled = '';
			foreach ($group AS $item) {
				if (is_array($item)) {
					$compiled .= '('.self::compileProperty($item, $b).')';
				} else {
					$compiled .= ($compiled ? ' ' : '').$item;
				}
			}
			$properties[] = $compiled;
		}
		return implode($b ? ', ' : ',', $properties);
	}

	/*protected static function clean($code) {
		$replace = Array(
			'/\/\*(?!!)[\d\D]*?\*\//' => '', // remove comments
			'/("(?:[^"\\\\]++|\\\\.)*+")?\s++/i' => '$1 ', // collapse whitespace to single space
		);
		return preg_replace(array_keys($replace), $replace, $code);
	}

	protected static function tokenise($code, $config, $sub = false) {

		// split declarations
		if (preg_match_all('/([^{]++)({([^{}]++|(?2))*})/', $code, $items, PREG_SET_ORDER)) {
			$css = Array(); // stores the compiled CSS
			foreach ($items AS $item) {

				// process media queries in their own block
				$isat = strpos($item[0], '@') === 0; // @selectors are special
				if ($isat && !in_array($item[1], Array('@font-face', '@import', '@charset'))) {
					$css[] = Array(
						'selectors' => $item[1],
						'properties' => self::tokenise(substr($item[2], 1, -1), $config, true)
					);

				// process rules
			} elseif (!empty($item[3]) && ($properties = self::tokeniseProperties($item[3], $config)) !== false) {
					$css[] = Array(
						'selectors' => self::tokeniseSelectors($item[1], $config),
						'properties' => $properties
					);
				}
			}
			return $css;
		}
		return false;
	}

	protected static function tokeniseSelectors($selector, $config) {
		$selector = preg_replace('/("(?:[^"\\\\]++|\\\\.)*+")\s?|\s?([()+>,])\s?|^\s|\s$/i', '$1$2', $selector);
		$selectors = explode(',', $selector);

		// sort selectors alphabetically - gzips better and enables better collapsing
		if ($config['sortselectors']) {
			sort($selectors);
		}
		return $selectors;
	}

	protected static function tokeniseProperties($code, $config) {
		$pat = '/([^:]++):((?:[^;\(\)!]*+(\((?:[^\(\)]++|(?3))*\))*+[^;\(\)!]*+)++)(!important)?(?:;|$)/';
		if (preg_match_all($pat, $code, $matches, PREG_SET_ORDER)) { // match properties
			$properties = Array();
			foreach ($matches AS $match) {
				// STORE THE PROPERTY ##################################
				$properties[] = Array(
					'property' => $match[1],
					'value' => self::minifyProperty($match[1], $match[2], $config),
					'important' => !empty($match[4])
				);
			}
			return $properties;
		}
		return false;
	}

	protected static function minifyProperty($prop, $value, $config) {
		return $value;

		// remove unit from zero values
		if ($config['removezerounits']) {
			$value = preg_replace('/((?: |^|$)0)(?:px|pt|pc|%|em|rem)/i', '$1', $value);
		}

		// strip zero from floats
		if ($config['removeleadingzero']) {
			$value = preg_replace('/( |^|$)0++(\.\d++)/i', '$1$2', $value);
		}

		// convert single quotes to double quotes
		if ($config['convertquotes']) {
			$value = preg_replace('/(?<!\\\\)\'([^\'\\\\]++|\\\\.)*+\'/i', '"$1"', $value);
		}

		// remove quotes
		if ($config['removequotes'] && !in_array($prop, Array('content', 'filter'))) {
			$value = preg_replace('/"([^\'\\\\]++|\\\\.)*+")/i', '$1', $value);
		}

		// shorten hex colours, not in filter
		if ($config['shortenhex'] && $prop != 'filter') {
			$value = preg_replace('/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#$1$2$3', $value);
		}

		// lowercase hex values
		if ($config['lowerhex']) {
			$value = preg_replace_callback('/(?<=[ ,])#(?:[a-f0-9]{6}|[a-f0-9]{3})/i', function ($match) {
				return strtolower($match[0]);
			}, $value);
		}
		return $value;
	}

	protected static function compile($rules, $min = true, $semiColon = false) {
		$output = '';
		foreach ($rules AS $item) {
			$output .= is_array($item['selectors']) ? implode($min ? ', ' : ',', $item['selectors']) : $item['selectors'];
			$output .= $min ? '{' : " {\n\t";
			if (is_array($item['properties'])) {
				$properties = Array();
				foreach ($item['properties'] AS $value) {
					$properties[] = $value['property'].($min ? ':' : ': ').$value['value'].($value['important'] ? ($min ? '' : ' ').'!important' : '');
				}
				$output .= implode(';'.($min ? '' : "\n\t"), $properties);
				var_dump($item['properties'], $properties);
			}
			$output .= ($semiColon ? '' : ';').($min ? '}' : "\n}\n");
		}
		return $output;
	}*/
}
