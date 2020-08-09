<?php
declare(strict_types = 1);
namespace hexydec\html;

class tokenise {

	/**
	 * @var string $value Stores the subject value to be tokenised
	 */
	protected $value = '';

	/**
	 * @var string $pattern Stores the regexp pattern to tokenise the string with
	 */
	protected $pattern = '';

	/**
	 * @var array $keys An array to map the regexp output with the token type
	 */
	protected $keys = [];

	/**
	 * @var int $pos The position within $value to retrieve the next token from
	 */
	protected $pos = 0;

	/**
	 * @var int $pointer The current token position
	 */
	protected $pointer = -1;

	/**
	 * @var array $tokens An array of captured tokens
	 */
	protected $tokens = [];

	/**
	 * Constructs a new tokeniser object
	 *
	 * @param array $tokens An associative array of token patterns, tokens will be returned with the key specified
	 * @param string $value The string to be tokenised
	 */
	public function __construct(array $tokens, string $value) {
		$this->pattern = '/\G('.implode(')|(', $tokens).')/u';
		$this->keys = array_keys($tokens);
		$this->value = $value;

		// generate the first token
		$this->next();
	}

	/**
	 * Retrieves the previous token (Note you can only retrieve the immediately preceeding token, you can't keep going backwards as the previous previous token is deleted when the next token is consumed)
	 *
	 * @return array The previous token or null if the token no longer exists
	 */
	public function prev() : ?array {
		return $this->pointer ? $this->tokens[--$this->pointer] : null;
	}

	/**
	 * Retrieves the current token
	 *
	 * @return array The currnet token or null if there is no token
	 */
	public function current() : ?array {
		return $this->tokens[$this->pointer] ?? null;
	}

	/**
	 * Retrieves the next token
	 *
	 * @return array The next token or null if there are no more tokens to retrieve
	 */
	public function next() : ?array {
		$pointer = $this->pointer + 1;

		// get cached token
		if (isset($this->tokens[$pointer])) {
			return $this->tokens[++$this->pointer];

		// extract next token
		} elseif (preg_match($this->pattern, $this->value, $match, PREG_UNMATCHED_AS_NULL, $this->pos)) {

			// go through tokens and find which one matched
			foreach ($this->keys AS $i => $key) {
				$i++; // 1 based array
				if ($match[$i] !== null) {
					$this->pos += strlen($match[$i]);

					// save the token
					$token = $this->tokens[++$this->pointer] = [
						'type' => $key,
						'value' => $match[$i]
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
