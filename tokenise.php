<?php
namespace hexydec\minify;

class tokenise {

	public static function tokenise($code, $config) {

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
}
