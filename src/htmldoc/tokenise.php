<?php
declare(strict_types = 1);
namespace hexydec\html;

class tokenise {

	protected $value = '';
	protected $pattern = '';
	protected $keys = [];
	protected $pos = 0;
	protected $pointer = -1;
	protected $tokens = [];

	public function __construct(array $tokens, string $value) {

		// prepare regexp and extract strings
		$patterns = [];
		foreach ($tokens AS $key => $item) {
			$patterns[] = '(?<'.$key.'>'.$item.')';
		}
		$this->pattern = '/\G'.implode('|', $patterns).'/u';
		$this->keys = array_keys($tokens);
		$this->value = $value;

		// generate the first token
		$this->next();
	}

	public function prev() : ?array {
		return $this->pointer ? $this->tokens[--$this->pointer] : null;
	}

	public function current() : ?array {
		return $this->tokens[$this->pointer] ?? null;
	}

	public function next() : ?array {
		$pointer = $this->pointer + 1;

		// get cached token
		if (isset($this->tokens[$pointer])) {
			return $this->tokens[++$this->pointer];

		// extract next token
		} elseif (preg_match($this->pattern, $this->value, $match, PREG_UNMATCHED_AS_NULL, $this->pos)) {

			// go through tokens and find which one matched
			foreach ($this->keys AS $key) {
				if ($match[$key] !== null) {
					$this->pos += strlen($match[$key]);

					// save the token
					$token = $this->tokens[++$this->pointer] = [
						'type' => $key,
						'value' => $match[$key]
					];

					// remove previous tokens to lower memory consumption, also makes the program faster with a smaller array to handle
					unset($this->tokens[$pointer - 2]);
					return $token;
				}
			}
		}
		return null;
	}
}
