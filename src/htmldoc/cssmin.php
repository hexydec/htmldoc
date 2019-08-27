<?php
namespace hexydec\html;

class cssmin {

   protected static $tokens = Array(
	   'whitespace' => '\s++',
	   'comment' => '\/\*(?!!)[\d\D]*?\*\/',
	   'quotes' => '(?<!\\\\)("(?:[^"\\\\]++|\\\\.)*+"|\'(?:[^\'\\\\]++|\	\\\.)*+\')',
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
   		'mergeselectors' => true,
   		'removeoverwrittenproperties' => true,
   		'sortproperties' => true,
   		'mergeblocks' => true,
   		'report' => false,
	   	'output' => 'minify'
   	);

	public static function minify(string $code, array $config = Array()) {
		$config = array_merge(self::$config, $config);
		if (($tokens = tokenise::tokenise($code, self::$tokens)) === false) {
			trigger_error('Could not tokenise input', E_USER_WARNING);
		} elseif (($ast = self::parse($tokens)) === false) {
			trigger_error('Input is not invalid', E_USER_WARNING);
		} elseif (($ast = self::minifyAst($ast, $config)) === false) {
			trigger_error('AST could not be minified', E_USER_WARNING);
		} elseif (($css = self::compile($ast, $config)) === false) {
			trigger_error('Could not compile CSS', E_USER_WARNING);
		} else {
			return $css;
		}
	}

	protected static function parse(array &$tokens) {
		$rules = Array();
		$select = true;
		$comment = false;
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
					} elseif ($token['type'] == 'string') {
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
				if (next($tokens)['type'] == 'colon') {
					$properties[$prop] = Array(
						'value' => self::parsePropertyValue($tokens, $important, $propcomment),
						'important' => $important,
						'semicolon' => ';',
						'comment' => $propcomment
					);
				} else {
					prev($tokens);
				}

			// end rule
			} elseif ($token['type'] == 'curlyclose') {
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
		} while (($token = next($tokens)) !== false);
		// print_r($rules);
		// exit();
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
					while (($token = next($tokens)) !== false) {
						if (!in_array($token['type'], Array('whitespace', 'comma', 'curlyopen'))) {
							$parts .= $token['value'];
						} else {
							prev($tokens);
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
					while (($token = next($tokens)) !== false) {
						if (!in_array($token['type'], Array('squareclose'))) {
							$parts .= $token['value'];
						} elseif ($token['type'] != 'whitespace') {
							prev($tokens);
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
					$join = $token['value'];
					break;
			}
		} while (($token = next($tokens)) !== false);
		return $selector;
	}

	protected static function parsePropertyValue(&$tokens, &$important = false, &$comment = false) {
		$properties = Array();
		$values = Array();
		$important = false;
		$comment = false;
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
			} elseif ($token['type'] != 'whitespace') {
				$values[] = $token['value'];
			} else {
				break;
			}
		}
		if ($values) {
			$properties[] = $values;
		}
		return $properties;
	}

	protected static function minifyAst(array $ast, array $config) {
		foreach ($ast AS &$item) {
			if (isset($item['media'])) {
				$item['rules'] = self::minifyAst($item['rules'], $config);
			} else {
				foreach ($item['properties'] AS $key => &$prop) {
					foreach ($prop['value'] AS &$group) {
						$group = self::minifyValues($key, $group, $config);
					}
					unset($group);
				}
				unset($prop);

				if ($config['sortproperties']) {
					ksort($item['properties']);
				}
				if ($config['removesemicolon']) {
					end($item['properties']);
					$item['properties'][key($item['properties'])]['semicolon'] = '';
				}
			}
		}
		unset($item);
		return $ast;
	}

	protected static function minifyValues($key, $values, $config) {
		foreach ($values AS &$value) {

			// value in brackets
			if (is_array($value)) {
				$value = self::minifyValues($key, $value, $config);
			} else {
				if ($config['removezerounits'] && preg_match('/^0(?:\.0*)?[a-z%]++$/i', $value)) {
					$value = '0';
				}
				if ($config['removeleadingzero'] && preg_match('/^0++(\.0*+[1-9][0-9%a-z]*+)$/', $value, $match)) {
					$value = $match[1];
				}
				if ($config['removequotes'] && $key != 'content' && preg_match('/^("|\')([^ \'"()]++)\\1$/i', $value, $match)) {
					$value = $match[2];
				} elseif ($config['convertquotes'] && strpos($value, "'") === 0) {
					$value = '"'.addcslashes(stripslashes(trim($value, "'")), "'").'"';
				}
				if ($config['shortenhex'] && preg_match('/^#(([a-f0-6])\\2)(([a-f0-6])\\4)(([a-f0-6])\\6)/i', $value, $match)) {
					$value = '#'.$match[2].$match[4].$match[6];
				}
				if ($config['lowerhex'] && preg_match('/^#[a-f0-6]{3,6}$/i', $value)) {
					$value = strtolower($value);
				}
				if ($config['mergeselectors']) {

				}
				if ($config['removeoverwrittenproperties']) {

				}
				if ($config['mergeblocks']) {

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
					$compiled .= ($compiled !== '' ? ' ' : '').$item;
				}
			}
			$properties[] = $compiled;
		}
		return implode($b ? ', ' : ',', $properties);
	}
}
