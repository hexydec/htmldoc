<?php
namespace hexydec\minify;

class tokenise {

	public static function tokenise($code, $config) {
		$re = '/('.implode(')|(', $config['tokens']).')/';
		if (preg_match_all($re, $code, $match)) {
			$token = Array();
			$keys = array_keys($config['tokens']);
			foreach ($match[0] AS $i => $item) {
				foreach ($keys AS $token => $type) {
					if ($match[$token+1][$i] !== '') {
						$tokens[] = Array(
							'type' => $type,
							'value' => $item
						);
					}
				}
			}
			return $tokens;
		}
		return false;
	}
}
