<?php
declare(strict_types = 1);
namespace hexydec\html;
use \hexydec\tokens\tokenise;

class selector {

	/**
	 * @var array $selectors Regexp components keyed by their corresponding codename for tokenising CSS selectors
	 */
	protected static array $tokens = [
		'quotes' => '(?<!\\\\)"(?:[^"\\\\]++|\\\\.)*+"',
		'comparison' => '[\\^*$<>|~]?=', // comparison operators for media queries or attribute selectors
		'join' => '\\s*[>+~]\\s*',
		'squareopen' => '\\[',
		'squareclose' => '\\]',
		'bracketopen' => '\\(',
		'bracketclose' => '\\)',
		'comma' => ',',
		'pseudo' => ':[A-Za-z-]++',
		'id' => '#[^ +>\.#{\\[,]++',
		'class' => '\.[^ +>\.#{\\[\\(\\),]++',
		'string' => '\\*|[^\\[\\]{}\\(\\):;,>+=~|\\^$!" #\\.*]++',
		'whitespace' => '\s++',
	];

	public function get(string $selector) {
		$tokens = new tokenise(self::$tokens, \trim($selector));
		return $this->parse($tokens);
	}

	/**
	 * Parses a CSS selector string
	 *
	 * @param string $selector The CSS selector string to parse
	 * @return array|bool An array of selector components
	 */
	public function parse(tokenise $tokens) {
		if (($token = $tokens->next()) !== null) {
			$selectors = $parts = [];
			$join = null;
			do {
				switch ($token['type']) {
					case 'id':
						$parts[] = [
							'id' => \mb_substr($token['value'], 1),
							'join' => $join
						];
						$join = null;
						break;

					case 'class':
						$parts[] = [
							'class' => \mb_substr($token['value'], 1),
							'join' => $join
						];
						$join = null;
						break;

					case 'string':
						$parts[] = [
							'tag' => $token['value'],
							'join' => $join
						];
						$join = null;
						break;

					case 'squareopen':
						$parts[] = $this->parseAttributes($tokens, $join);
						$join = null;
						break;

					case 'pseudo':
						$sub = null;
						if (($bracket = $tokens->next()) !== null && $bracket['type'] === 'bracketopen') {
							$sub = $this->parse($tokens);
						} elseif ($bracket) {
							$tokens->prev();
						}
						$parts[] = [
							'pseudo' => \mb_substr($token['value'], 1),
							'sub' => $sub,
							'join' => $join
						];
						$join = null;
						break;

					case 'join':
						$join = \trim($token['value']);
						break;

					case 'whitespace':
						if ($parts) {
							$join = ' ';
						}
						break;

					case 'comma':
						$selectors[] = $parts;
						$parts = [];
						break;

					case 'bracketclose':
						$selectors[] = $parts;
						$parts = [];
						break;
				}
			} while (($token = $tokens->next()) !== null);
			if ($parts) {
				$selectors[] = $parts;
			}
			return $selectors;
		}
		return false;
	}

	protected function parseAttributes(tokenise $tokens, ?string $join = null) {
		$item = ['join' => $join, 'sensitive' => true];
		while (($token = $tokens->next()) !== null && $token['type'] !== 'squareclose') {

			// record comparison
			if ($token['type'] === 'comparison') {
				$item['comparison'] = $token['value'];

			// handle string or quotes
			} elseif (\in_array($token['type'], ['string', 'quotes'], true)) {

				// strip quotes
				if ($token['type'] === 'quotes') {
					$token['value'] = \stripslashes(\mb_substr($token['value'], 1, -1));
				}

				// set attribute
				if (!isset($item['attribute'])) {
					$item['attribute'] = $token['value'];

				// set value
				} elseif (!isset($item['value'])) {
					$item['value'] = $token['value'];

				// set sensitive
				} elseif ($token['value'] === 'i') {
					$item['sensitive'] = false;
				}
			}
		}
		return $item;
	}
}
