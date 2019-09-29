<?php
namespace hexydec\html;

class cssmin {

   protected static $tokens = Array(
	   'whitespace' => '\s++',
	   'comment' => '\\/\\*[\d\D]*?\\*\\/',
	   'quotes' => '(?<!\\\\)("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\\\\.)*+\')',
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
	   'string' => '!?[^\[\]{}\(\):;,>+=~\^$!"\/ \n\r\t]++'
   );

   	protected static $config = Array(
   		'removesemicolon' => true,
   		'removezerounits' => true,
   		'removeleadingzero' => true,
   		'convertquotes' => true,
   		'removequotes' => true,
   		'shortenhex' => true,
   		'lowerhex' => true,
   		'sortselectors' => true,
   		// 'mergeselectors' => true,
   		// 'removeoverwrittenproperties' => true,
   		// 'sortproperties' => true,
   		// 'mergeblocks' => true,
   		'email' => false,
		'maxline' => false,
	   	'output' => 'minify'
   	);

	public static function minify(string $code, array $config = Array()) {
		$config = array_merge(self::$config, $config);

		// set email options
		if ($config['email']) {
			$config['maxline'] = 800;
			$config['shortenhex'] = false;
			$config['sortselectors'] = false;
			// $config['mergeselectors'] = false;
			// $config['removeoverwrittenproperties'] = false;
			$config['sortproperties'] = false;
			// $config['mergeblocks'] = false;
		}

		// tokenise the input CSS
		if (($tokens = tokenise::tokenise($code, self::$tokens)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);

		// parse the tokens
		} elseif (($ast = self::parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);

		// minify the internal representation
		} elseif (($ast = self::minifyAst($ast, $config)) === false) {
			trigger_error('AST could not be minified', E_USER_WARNING);

		// compile the output
		} elseif (($css = self::compile($ast, $config)) === false) {
			trigger_error('Could not compile CSS', E_USER_WARNING);

		// return CSS
		} else {
			return $css;
		}
	}

	protected static function parse(array &$tokens) {
		$rules = Array();
		$select = true;
		$comment = null;
		$media = false;
		$selectors = Array();
		$properties = Array();
		$token = current($tokens);
		do {

			// process selectors
			if ($select) {
				if ($token['type'] != 'whitespace') {
					if ($token['type'] == 'comment') {
						$comment = $token['value'];
					} elseif ($token['type'] == 'curlyclose') {
						return $rules;
					} elseif (in_array($token['type'], ['string', 'colon'])) {
						if ($token['value'] == '@media') {
							$rules[] = Array(
								'media' => self::parseMediaQuery($tokens),
								'rules' => self::parse($tokens),
								'comment' => $comment
							);
							$comment = false;
						} else {
							// prev($tokens);
							$selectors[] = self::parseSelectors($tokens);
							if (current($tokens)['type'] == 'curlyopen') {
								$select = false;
							}
						}
					}
				}

			// process properties
			} elseif ($token['type'] == 'string') {
				$prop = $token['value'];
				while (($token = next($tokens)) !== false) {
					if ($token['type'] == 'colon') {
						$important = false;
						$properties[] = Array(
							'property' => $prop,
							'value' => self::parsePropertyValue($tokens, $important, $propcomment),
							'important' => $important,
							'semicolon' => ';',
							'comment' => $propcomment
						);
						break;
					}
				}

			// end rule
			} elseif ($token['type'] == 'curlyclose') {
				if ($selectors && $properties) {
					$rules[] = Array(
						'selectors' => $selectors,
						'properties' => $properties,
						'comment' => $comment
					);
				}
				$selectors = Array();
				$properties = Array();
				$select = true;
				$comment = false;
			}
		} while (($token = next($tokens)) !== false);
		return $rules;
	}

	protected static function parseMediaQuery(array &$tokens) {
		$media = Array();
		$default = $rule = Array(
			'media' => 'all',
			'only' => false,
			'not' => false,
			'properties' => Array()
		);
		while (($token = next($tokens)) !== false) {
			switch ($token['type']) {
				case 'string':
					if ($token['value'] == 'only') {
						$rule['only'] = true;
					} elseif ($token['value'] == 'not') {
						$rule['not'] = true;
					} elseif ($token['value'] != 'and') {
						$rule['media'] = $token['value'];
					}
					break;
				case 'bracketopen':
					$compare = false;
					while (($token = next($tokens)) !== false && $token['type'] != 'bracketclose') {
						if ($token['type'] == 'string') {
							if (!$compare) {
								$prop = $token['value'];
							} elseif ($compare == ':') {
								$rule['properties'][$prop] = $token['value'];
								$compare = false;
							} else {
								if (intval($prop)) {
									$rule['properties']['min-'.$token['value']] = $prop;
									$prop = 'max'.$token['value'];
								} else {
									$rule['properties'][$prop] = $token['value'];
								}
							}
						} elseif ($token['type'] == 'colon') {
							$compare = ':';
						} elseif ($token['type'] == 'comparison' && $token['value'] == '<=') {
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

	protected static function parseSelectors(array &$tokens) {
		$selector = Array();
		$join = false;
		$token = current($tokens);
		do {
			switch ($token['type']) {
				case 'whitespace':
					if (!$join) {
						$join = ' ';
					}
					break;
				case 'string':
					$selector[] = Array(
						'selector' => $token['value'],
						'join' => $join
					);
					$join = false;
					break;
				case 'colon':
					$parts = ':';
					$brackets = false;
					while (($token = next($tokens)) !== false) {

						// capture brackets
						if ($brackets || $token['type'] == 'bracketopen') {
							$brackets = true;
							if ($token['type'] != 'whitespace') {
								$parts .= $token['value'];
								if ($token['type'] == 'bracketclose') {
									break;
								}
							}

						// capture selector
						} elseif (!in_array($token['type'], Array('whitespace', 'comma', 'curlyopen'))) {
							$parts .= $token['value'];

						// stop here
						} else {
							prev($tokens);
							break;
						}
					}

					// save selector
					$selector[] = Array(
						'selector' => $parts,
						'join' => $join
					);
					$join = false;
					break;
				case 'squareopen':
					$parts = '';
					while (($token = next($tokens)) !== false) {
						if ($token['type'] != 'whitespace') {
							if ($token['type'] != 'squareclose') {
								$parts .= $token['value'];
							} else {
								prev($tokens);
								break;
							}
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
					$join = $token['value'];
					break;
			}
		} while (($token = next($tokens)) !== false);
		return $selector;
	}

	protected static function parsePropertyValue(array &$tokens, bool &$important = false, string &$comment = null) {
		$properties = Array();
		$values = Array();
		$important = false;
		$comment = null;
		while (($token = next($tokens)) !== false) {
			if ($token['type'] == 'comma') {
				$properties[] = $values;
				$values = Array();
			} elseif ($token['value'] == '!important') {
				$important = true;
			} elseif ($token['type'] == 'bracketopen') {
				$values[] = self::parsePropertyValue($tokens);
			} elseif (in_array($token['type'], Array('semicolon', 'bracketclose'))) {
				while (($token = next($tokens)) !== false) {
					if ($token['type'] == 'comment') {
						$comment = $token['value'];
					} elseif ($token['type'] != 'whitespace') {
						prev($tokens);
						break;
					}
				}
				break;
			} elseif ($token['type'] == 'curlyclose') {
				prev($tokens);
				break;
			} elseif ($token['type'] != 'whitespace') {
				$values[] = $token['value'];
			}
		}
		if ($values) {
			$properties[] = $values;
		}
		return $properties;
	}

	protected static function minifyAst(array $ast, array $config) {
		foreach ($ast AS &$item) {

			// minify media query
			if (isset($item['media'])) {
				$item['rules'] = self::minifyAst($item['rules'], $config);

			// minify rule
			} else {

				// minify values
				foreach ($item['properties'] AS &$prop) {
					foreach ($prop['value'] AS &$group) {
						$group = self::minifyValues($prop['property'], $group, $config);
					}
					unset($group);
				}
				unset($prop);

				// remove training semi-colon
				if ($config['removesemicolon']) {
					end($item['properties']);
					$item['properties'][key($item['properties'])]['semicolon'] = '';
				}
			}
		}
		unset($item);
		return $ast;
	}

	protected static function minifyValues(string $key, array $values, array $config) {
		foreach ($values AS &$value) {

			// value in brackets
			if (is_array($value)) {
				$value = self::minifyValues($key, $value, $config);
			} else {
				if ($config['removezerounits'] && preg_match('/^0(?:\.0*)?([a-z%]++)$/i', $value, $match)) {
					$value = '0';
					if ($match[1] == 'ms') {
						$match[1] = 's';
					}
					if ($match[1] == 's') {
						$value .= 's';
					}
				}
				if ($config['removeleadingzero'] && preg_match('/^0++(\.0*+[1-9][0-9%a-z]*+)$/', $value, $match)) {
					$value = $match[1];
				}
				if ($config['removequotes'] && $key != 'content' && preg_match('/^("|\')([^ \'"()]++)\\1$/i', $value, $match)) {
					$value = $match[2];
				} elseif ($config['convertquotes'] && mb_strpos($value, "'") === 0) {
					$value = '"'.addcslashes(stripslashes(trim($value, "'")), "'").'"';
				}
				if ($config['shortenhex'] && preg_match('/^#(([a-f0-6])\\2)(([a-f0-6])\\4)(([a-f0-6])\\6)/i', $value, $match)) {
					$value = '#'.$match[2].$match[4].$match[6];
				}
				if ($config['lowerhex'] && preg_match('/^#[a-f0-6]{3,6}$/i', $value)) {
					$value = mb_strtolower($value);
				}
			}
		}
		unset($value);
		return $values;
	}

	protected static function compile(array $ast, array $config, int $indent = 0) {
		$b = $config['output'] != 'minify';
		$tabs = $b ? str_repeat("\t", $indent) : '';
		$css = '';
		$len = 0;
		foreach ($ast AS $item) {
			$rule = '';

			// comment
			if ($b && $item['comment']) {
				$rule .= $tabs.$item['comment']."\n";
			}

			// build properties
			if (isset($item['media'])) {
				$rule .= '@media';
				foreach ($item['media'] AS $i => $media) {
					$join = $b ? ' ' : '';
					if ($media['only']) {
						$rule .= ' only';
						$join = ' and ';
					}
					if ($media['media']) {
						$rule .= ' '.$media['media'];
						$join = ' and ';
					}
					if ($media['not']) {
						$rule .= ' not';
						$join = ' and ';
					}
					foreach ($media['properties'] AS $prop => $value) {
						$rule .= $join.'('.$prop.':'.($b ? ' ' : '').$value.')';
						$join = ' and ';
					}
				}
				$rule .= $b ? " {\n" : '{';
				$rule .= self::compile($item['rules'], $config, $indent + 1);

			// build selectors
			} else {
				foreach ($item['selectors'] AS $i => $selector) {
					if ($i) {
						$rule .= $b ? ', ' : ',';
					} else {
						$rule .= $tabs;
					}
					foreach ($selector AS $select) {
						if (!empty($select['join'])) {
							$rule .= $b && $select['join'] != ' ' ? ' '.$select['join'].' ' : $select['join'];
						}
						$rule .= $select['selector'];
					}
				}
				$rule .= $b ? ' {' : '{';

				// compile properties
				foreach ($item['properties'] AS $value) {
					$rule .= ($b ? "\n\t".$tabs : '').$value['property'].($b ? ': ' : ':');
					$rule .= self::compileProperty($value['value'], $b);
					if ($value['important']) {
						$rule .= ($b ? ' ' : '').'!important';
					}
					$rule .= $value['semicolon'];
					if ($b && $value['comment']) {
						$rule .= ' '.$value['comment'];
					}
				}
			}

			$rule .= $b ? "\n".$tabs."}\n\n" : '}';

			// break long lines in email
			if (!$b && $config['maxline']) {
				$rlen = mb_strlen($rule);
				if ($len + $rlen > $config['maxline']) {
					$rule .= "\n";
				}
				$len += $rlen;
			}

			// add to css
			$css .= $rule;
		}
		return rtrim($css);
	}

	protected static function compileProperty(array $value, int $b, string $join = ' ') {
		$properties = Array();
		foreach ($value AS $group) {
			$compiled = '';
			foreach ($group AS $item) {
				if (is_array($item)) {
					$compiled .= '('.self::compileProperty($item, $b, $group[0] == 'calc' ? '' : $join).')';
				} else {
					$compiled .= ($compiled !== '' ? $join : '').$item;
				}
			}
			$properties[] = $compiled;
		}
		return implode($b ? ', ' : ',', $properties);
	}
}
