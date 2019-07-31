<?php
namespace hexydec\html;

class tokenise {

	/**
	 * Tokenises the input using the supplied patterns
	 *
	 * @param String $input The string to be tokenised
	 * @param Array $tokens An associative array of regexp patterns, keyed by their token name
	 * @return Array An array of tokens, each token is an array containing the keys 'type' and 'value'
	 */
	public static function tokenise(String $input, Array $tokens) {

		// prepare regexp and extract strings
		$patterns = Array();
		foreach ($tokens AS $key => $item) {
			$patterns[] = '(?<'.$key.'>'.$item.')';
		}
		$re = '/'.implode('|', $patterns).'/u';

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
		if (($remain = preg_replace_callback($re, $callback, $input)) !== '') {
			//\var_dump($remain);
		}
		return $output ? $output : false;

		// if (preg_match_all($re, $input, $match, PREG_UNMATCHED_AS_NULL)) {
		// 	$keys = array_keys($tokens);
		// 	$count = count($match[0]);
		//
		// 	// delete input to save memory
		// 	unset($input);
		// 	$match = array_intersect_key($match, $tokens);
		//
		// 	// build tokens into types
		// 	$output = Array();
		// 	for ($i = 0; $i < $count; $i++) {
		//
		// 		// go through tokens and find which one matched
		// 		foreach ($keys AS $key) {
		// 			if (isset($match[$key][$i])) {
		// 				$output[] = Array(
		// 					'type' => $key,
		// 					'value' => $match[$key][$i]
		// 				);
		// 				break;
		// 			}
		// 		}
		// 	}
		// 	return $output;
		// }
		// return false;
	}
}
