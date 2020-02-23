<?php
namespace hexydec\html;

class tokenise {

	/**
	 * Tokenises the input using the supplied patterns
	 *
	 * @param string $input The string to be tokenised
	 * @param array $tokens An associative array of regexp patterns, keyed by their token name
	 * @return array An array of tokens, each token is an array containing the keys 'type' and 'value'
	 */
	public static function tokenise(string $input, array $tokens) {

		// prepare regexp and extract strings
		$patterns = Array();
		foreach ($tokens AS $key => $item) {
			$patterns[] = '(?<'.$key.'>'.$item.')';
		}
		$re = '/\G'.implode('|', $patterns).'/u';

		$output = Array();
		$keys = array_keys($tokens);
		$callback = function ($match) use ($keys, &$output) {

			// go through tokens and find which one matched
			foreach ($keys AS $key) {
				if ($match[$key] !== '') {
					$output[] = Array(
						'type' => $key,
						'value' => $match[$key]
					);
					break;
				}
			}
			return '';
		};
		preg_replace_callback($re, $callback, $input);
		return $output ? $output : false;
	}
}
